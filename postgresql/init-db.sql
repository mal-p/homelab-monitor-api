-- Database Initialization Script for Home Monitoring
-- This script creates the database, role, tables, and TimescaleDB hypertable

-- Create the database
CREATE DATABASE homelab;

-- Connect to the homelab database
\c homelab

-- TimescaleDB extension should already be present in Docker image.
CREATE EXTENSION IF NOT EXISTS timescaledb;

-- Create the role
CREATE ROLE "homelab-role" WITH
    LOGIN
    PASSWORD 'homelab-password'
    NOSUPERUSER
    INHERIT
    NOCREATEDB
    NOCREATEROLE
    NOREPLICATION;

-- Grant privileges on the database
GRANT CONNECT ON DATABASE homelab TO "homelab-role";
GRANT USAGE ON SCHEMA public TO "homelab-role";
GRANT CREATE ON SCHEMA public TO "homelab-role";

-- Create devices, types and measured parameters tables
CREATE TABLE device_types (
    id            SERIAL                    PRIMARY KEY,
    name          VARCHAR(255)              UNIQUE NOT NULL,
    description   TEXT
);

CREATE TABLE devices (
    id            SERIAL                    PRIMARY KEY,
    type_id       INTEGER                   NOT NULL,
    name          VARCHAR(255)              NOT NULL,
    serial_number VARCHAR(100)              UNIQUE NOT NULL,
    mpan          VARCHAR(100)              UNIQUE,
    location      VARCHAR(255),
    description   TEXT,
    is_active     BOOLEAN                   NOT NULL     DEFAULT true,
    created_at    TIMESTAMP WITH TIME ZONE  NOT NULL     DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP WITH TIME ZONE  NOT NULL     DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_devices_type FOREIGN KEY (type_id) REFERENCES device_types(id) ON DELETE CASCADE
);

COMMENT ON COLUMN devices.serial_number IS 'Device ID 1. Octopus serial or Govee H5075 MAC address';
COMMENT ON COLUMN devices.mpan          IS 'Device ID 2. Octopus MPAN';

CREATE TYPE param_alarm_type AS ENUM ('none', 'low', 'high');

CREATE TABLE device_parameters (
    id                SERIAL                    PRIMARY KEY,
    device_id         INTEGER                   NOT NULL,
    name              VARCHAR(255)              NOT NULL,
    unit              VARCHAR(50),
    alarm_type        param_alarm_type          NOT NULL     DEFAULT 'none',
    alarm_trigger     DOUBLE PRECISION          NOT NULL     DEFAULT 0.0,
    alarm_hysteresis  DOUBLE PRECISION          NOT NULL     DEFAULT 0.0,
    alarm_active      BOOLEAN                   NOT NULL     DEFAULT false,
    alarm_updated_at  TIMESTAMP WITH TIME ZONE  NOT NULL     DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_parameters_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- Create hypertable for time-series data
CREATE TABLE device_data (
    time          TIMESTAMP WITH TIME ZONE  NOT NULL,
    parameter_id  INTEGER                   NOT NULL,
    value         DOUBLE PRECISION          NOT NULL,

    CONSTRAINT fk_data_parameter FOREIGN KEY (parameter_id) REFERENCES device_parameters(id) ON DELETE CASCADE
) WITH (
    tsdb.hypertable,
    tsdb.partition_column = 'time',
    tsdb.segmentby        = 'parameter_id',
    tsdb.orderby          = 'time DESC'
);

CREATE UNIQUE INDEX idx_device_data_paramid_time ON device_data(parameter_id, time);

-- Grant privileges on tables to homelab-role
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO "homelab-role";
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO "homelab-role";

-- Set default privileges for future tables
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO "homelab-role";

ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO "homelab-role";

-- Create a view for recent device data (last 24 hours)
CREATE VIEW recent_device_data AS
SELECT time_bucket('60 minutes', time) AS hour_bucket,
    COUNT(*),
    parameter_id,
    MIN(value) AS min_value,
    MAX(value) AS max_value,
    AVG(value) AS avg_value
FROM device_data
    WHERE time > NOW() - INTERVAL '1 day'
    GROUP BY hour_bucket, parameter_id
    ORDER BY hour_bucket DESC, avg_value DESC, max_value DESC, min_value DESC;

GRANT SELECT ON recent_device_data TO "homelab-role";

--
-- Clone unit test database
--
CREATE DATABASE homelab_test
    WITH TEMPLATE homelab
    OWNER "homelab-role";

-- Connect to the homelab database
\c homelab_test

-- Unit tests truncate rather than drop table created in this script
GRANT TRUNCATE ON ALL TABLES IN SCHEMA public TO "homelab-role";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT TRUNCATE ON TABLES TO "homelab-role";

-- Output success message
\echo 'Database initialization completed successfully!'
\echo '  - Database: homelab'
\echo '  - Role: homelab-role'
\echo '  - Tables: device_types, devices, device_parameters, device_data (hypertable)'
\echo '  - View: recent_device_data'

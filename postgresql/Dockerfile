FROM timescale/timescaledb:latest-pg17

# Locale
ENV LANG en_GB.UTF-8

# Copy database initialization script
COPY init-db.sql /docker-entrypoint-initdb.d/

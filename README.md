# Home Monitor API

A Laravel-based API for monitoring and tracking home devices with time-series data storage, alarm notifications, and automated data ingestion.

## Stack
- **Backend**: Laravel 12.x (PHP 8.4+)
- **Database**: PostgreSQL with TimescaleDB extension
- **Authentication**: Laravel Sanctum
- **Notifications**: AWS SNS
- **API Documentation**: L5-Swagger (OpenAPI 3.0)
- **Testing**: Pest PHP
- **Containerization**: Docker & Docker Compose


## Installation

### 1. Build Docker Containers
```bash
cd homelab-monitor-api
docker compose build
```
If running on a Raspberry Pi you may need to add `cgroup_enable=memory` to `/boot/firmware/cmdline.txt`.  
**NOTE** the container build step takes ~20 minutes on a Raspberry Pi 4.

### 2. Start Docker Containers
Adjust resource limits in `docker-compose.yml` as needed:
```yml
    limits:
        cpus: "0.50"
        memory: 256M
```

Running `docker compose up -d` will start:
- `php_apache`: PHP 8.4 + Apache web server
- `postgres`: PostgreSQL with TimescaleDB

PostgreSQL data is stored in the `timescale_pg_volume` Docker volume.

Optionally copy the `homelab-monitor.service` systemd service file to `/etc/systemd/system/` and run `systemctl daemon-reload`.

### 3. Test Database Access
Database credentials are configured in `postgresql/database.env`.
```bash
docker compose up -d
docker exec -it postgres psql -U postgres -d homelab
homelab=# \dt
```

### 4. Configure Environment Variables
Copy the environment file (`cp laravel/src/home-monitor/.env.devel laravel/src/home-monitor/.env`) and configure:
```env
# App Configuration
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8000,localhost:8000

# AWS SNS Configuration
AWS_SNS_REGION=us-east-1
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_SNS_TOPIC_ARN=arn:aws:sns:us-east-1:123456789012:your-topic

# Device Configuration
ELECTRICITY_SUPPLIER_API_KEY=""
ELECTRICITY_DEVICE_SN=""
ELECTRICITY_DEVICE_MPAN=""
```

### 5. Initialize Laravel Application
Access the PHP container:

```bash
docker exec -it php_apache /bin/bash
cd home-monitor
```

Install dependencies and set up the application:

```bash
# Install PHP dependencies
composer install
# Generate application key
php artisan key:generate
# Run migrations
php artisan migrate
# Seed the database
php artisan db:seed
```

## Usage

### Starting the Application
The application is accessible at [http://localhost:8080](http://localhost:8080) when Docker containers are running (host port number is configured in `laravel/Dockerfile`).

### Documentation
View the interactive [API documentation](http://localhost:8080/api/documentation).

### Test Access
Create an access token (returned as part of JSON response):
```bash
curl -X 'POST'                              \
    'http://localhost:8080/api/users/login' \
    -H 'accept: application/json'           \
    -H 'Content-Type: application/json'     \
    -d '{
        "email": "homelab@localhost",
        "password": "password",
        "device_name": "test-local"
    }'
```

Query the seeded devices:
```bash
curl -X 'GET'                                   \
    'http://localhost:8080/api/devices?page=1'  \
    -H 'accept: application/json'               \
    -H 'Authorization: Bearer <RETURNED_TOKEN>'
```

### Scheduled Tasks
Test data ingestion manually:
```bash
php artisan device:fetch-octopus-data --parameter-id=1
```

Add a root user crontab entry to automate Laravel console routes:
```bash
* * * * * docker exec -w /var/www/html/home-monitor php_apache php artisan schedule:run >> /dev/null 2>&1
```


## Development

### Running Tests
```bash
# Run Pest tests
php artisan test
```

### Regenerate Documentation
OpenAPI YML reference:
```bash
./vendor/bin/openapi app -o openapi.yaml
```

HTML documentation:
```bash
php artisan l5-swagger:generate
```


## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

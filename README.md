# Weather Forecast API

REST API for managing cities and retrieving 7-day weather forecasts. Built with Symfony 7.4, PHP 8.4, MySQL, and Memcached.

## Requirements

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/)

## Installation

```bash
./install.sh
```

The script will:
1. Create `.env.local` with a generated `APP_SECRET`
2. Build and start Docker containers
3. Install Composer dependencies
4. Run database migrations
5. Load fixtures

To rebuild containers from scratch (e.g. after Dockerfile changes):

```bash
./install.sh --fresh
```

## Running the application

```bash
docker compose up -d
docker compose down
```

API is available at `http://localhost:8080` by default (configurable via `NGINX_PORT` in `.env.local`).

Interactive documentation (Swagger UI): `http://localhost:8080/api/doc`

## API

### Health

| Method | Endpoint       | Description  |
|--------|----------------|--------------|
| `GET`  | `/api/health`  | Health check |

### Cities — `/api/v1/cities`

| Method   | Endpoint                | Description                          |
|----------|-------------------------|--------------------------------------|
| `GET`    | `/api/v1/cities`        | List all cities                      |
| `POST`   | `/api/v1/cities`        | Create a city                        |
| `GET`    | `/api/v1/cities/{id}`   | Get a city by ID                     |
| `PUT`    | `/api/v1/cities/{id}`   | Update a city                        |
| `DELETE` | `/api/v1/cities/{id}`   | Delete a city                        |

### Weather — `/api/v1/weather`

| Method | Endpoint                              | Description                                                      |
|--------|---------------------------------------|------------------------------------------------------------------|
| `POST` | `/api/v1/weather/forecast/city`       | Get 7-day forecast by city name (returns `300` if name is ambiguous) |
| `GET`  | `/api/v1/weather/forecast/city/{id}`  | Get 7-day forecast by city ID (use after `300` disambiguation)   |
| `POST` | `/api/v1/weather/forecast/coordinates`| Get 7-day forecast by coordinates                                |

### Authentication

All `/api/v1/*` endpoints require an `X-API-Key` header:

```
X-API-Key: <your-api-key>
```

Missing or invalid key returns `401 Unauthorized`. The key is configured via `API_KEY` in `.env.local` (default: `dev-api-key`).

### Rate limiting

Handled by Nginx (`docker/nginx/nginx.conf`):

- Global: **60 requests / minute** per IP
- Forecast endpoints (`/api/v1/weather/forecast/*`): **10 requests / minute** per IP

Exceeded limits return `429 Too Many Requests`.

## Development

All commands run inside the PHP container:

```bash
docker exec -it weather-forecast-php sh
```

### Database

```bash
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:diff   # generate migration from entity changes
php bin/console doctrine:fixtures:load     # reload fixture data
```

### Code quality

```bash
# Tests
vendor/bin/phpunit

# Static analysis (PHPStan level 8)
vendor/bin/phpstan analyse

# Coding standard (Slevomat + phpcs)
vendor/bin/phpcs
vendor/bin/phpcbf   # auto-fix
```

Or from the host machine:

```bash
docker exec weather-forecast-php vendor/bin/phpunit
docker exec weather-forecast-php vendor/bin/phpstan analyse
docker exec weather-forecast-php vendor/bin/phpcs
```

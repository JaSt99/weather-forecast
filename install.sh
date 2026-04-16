#!/usr/bin/env bash
set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info()    { echo -e "${GREEN}[INFO]${NC} $1"; }
warning() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

FRESH=false
for arg in "$@"; do
    case $arg in
        --fresh) FRESH=true ;;
        *) error "Unknown argument: $arg" ;;
    esac
done

command -v docker >/dev/null 2>&1 || error "Docker is not installed."
docker compose version >/dev/null 2>&1 || error "Docker Compose (v2) is not installed."

# --- .env setup ---
if [ ! -f .env.local ]; then
    info "Creating .env.local ..."
    cp .env .env.local
    SECRET=$(LC_ALL=C tr -dc 'a-zA-Z0-9' </dev/urandom | head -c 32 || true)
    sed -i.bak "s/change_me_in_production_use_random_32_chars/${SECRET}/" .env.local && rm -f .env.local.bak
    info ".env.local created with a random APP_SECRET."
else
    warning ".env.local already exists, skipping."
fi

# --- Build & start containers ---
if [ "$FRESH" = true ]; then
    info "Building Docker images (no cache)..."
    docker compose build --no-cache
else
    info "Building Docker images (using cache)..."
    docker compose build
fi

info "Starting containers..."
docker compose up -d

# --- Wait for PHP container ---
info "Waiting for PHP container to be ready..."
for i in $(seq 1 15); do
    if docker compose exec -T php php -v >/dev/null 2>&1; then
        break
    fi
    sleep 1
done

# --- Composer install ---
info "Installing Composer dependencies..."
docker compose exec -T php composer install --no-interaction --prefer-dist --optimize-autoloader

# --- Cache warm-up ---
info "Warming up Symfony cache..."
docker compose exec -T php php bin/console cache:warmup

# --- Wait for MySQL ---
info "Waiting for MySQL to be ready..."
for i in $(seq 1 30); do
    if docker compose exec -T mysql mysqladmin ping -u root -p"${MYSQL_ROOT_PASSWORD:-root}" --silent 2>/dev/null; then
        break
    fi
    sleep 2
done

# --- Migrations ---
info "Running database migrations..."
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

# --- Fixtures ---
info "Loading data fixtures..."
docker compose exec -T php php bin/console doctrine:fixtures:load --no-interaction

# --- Done ---
PORT=$(grep NGINX_PORT .env.local 2>/dev/null | cut -d= -f2 || echo 8080)
echo ""
echo -e "${GREEN}=============================="
echo -e " Installation complete!"
echo -e "==============================${NC}"
echo ""
echo -e "  API:        http://localhost:${PORT}/api/health"
echo -e "  Swagger UI: http://localhost:${PORT}/api/doc"
echo -e "  OpenAPI:    http://localhost:${PORT}/api/doc.json"
echo ""
echo -e "Useful commands:"
echo -e "  docker compose exec php php bin/console <command>"
echo -e "  docker compose logs -f"
echo -e "  docker compose down"
echo ""

#!/bin/sh
set -e

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

mkdir -p .docker

ENV_FILE=".docker/stack.env"
if [ ! -f "$ENV_FILE" ]; then
  U="getfy_$(tr -dc 'a-z0-9' < /dev/urandom | head -c 8)"
  P="$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 32)"
  R="$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 32)"

  cat > "$ENV_FILE" <<EOF
DB_DATABASE=getfy
DB_USERNAME=$U
DB_PASSWORD=$P
APP_URL=http://localhost
GETFY_HTTP_PORT=80
MYSQL_DATABASE=getfy
MYSQL_USER=$U
MYSQL_PASSWORD=$P
MYSQL_ROOT_PASSWORD=$R
EOF
fi

docker compose --env-file "$ENV_FILE" up --build -d

#!/usr/bin/env sh
set -eu
URL="http://localhost:8080"

if command -v php >/dev/null 2>&1; then
  echo "Starting AccessHub with PHP at $URL"
  php -S localhost:8080 -t public
  exit $?
fi

if command -v docker >/dev/null 2>&1; then
  echo "Starting AccessHub with Docker at $URL"
  docker compose up --build
  exit $?
fi

echo "Install PHP 8.2+ or Docker Desktop, then run this script again."
exit 1

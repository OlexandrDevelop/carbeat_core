#!/usr/bin/env bash
set -e

COMPOSE_DIR="$(cd "$(dirname "$0")" && pwd)"

# Tear down existing containers and prune stale networks so Docker doesn't try
# to attach containers to network IDs that were invalidated after a reboot.
docker compose -f "$COMPOSE_DIR/docker-compose.yml" down --remove-orphans 2>/dev/null || true
docker network prune -f >/dev/null 2>&1 || true

docker compose -f "$COMPOSE_DIR/docker-compose.yml" up -d

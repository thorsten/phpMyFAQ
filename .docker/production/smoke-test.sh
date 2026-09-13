#!/bin/sh
#
# Smoke test for a phpMyFAQ production image: starts MariaDB and the image,
# lets the entrypoint install headlessly and checks the HTTP behaviour that
# the compose file and the HEALTHCHECK rely on.
#
#   .docker/production/smoke-test.sh ghcr.io/thorsten/phpmyfaq:local [host port]
#
# Set SMOKE_DB_TYPE=sqlite3 to install into a SQLite file instead of starting
# a MariaDB container.

set -eu

IMAGE="${1:?usage: smoke-test.sh IMAGE [PORT]}"
PORT="${2:-8089}"
NAME="pmf-smoke-$$"
NETWORK="${NAME}-net"
BASE_URL="http://localhost:${PORT}"
FAILED=0

cleanup() {
    if [ "$FAILED" != "0" ]; then
        echo "--- container logs ($NAME) ---"
        docker logs "$NAME" 2>&1 | tail -80 || true
    fi
    docker rm -f -v "$NAME" "${NAME}-db" >/dev/null 2>&1 || true
    docker network rm "$NETWORK" >/dev/null 2>&1 || true
}
trap cleanup EXIT

fail() {
    echo "FAIL: $*" >&2
    FAILED=1
    exit 1
}

expect_status() {
    path="$1"
    expected="$2"
    actual=$(curl -s -o /dev/null -w '%{http_code}' "${BASE_URL}${path}")
    [ "$actual" = "$expected" ] || fail "GET ${path}: expected HTTP ${expected}, got ${actual}"
    echo "ok   GET ${path} -> ${actual}"
}

docker network create "$NETWORK" >/dev/null

# SMOKE_DB_TYPE=sqlite3 installs into a SQLite file instead of starting MariaDB.
if [ "${SMOKE_DB_TYPE:-mysqli}" = "sqlite3" ]; then
    DB_TYPE=sqlite3
    DB_HOST=/var/www/html/content/core/data/smoke-test.sqlite
else
    DB_TYPE=mysqli
    DB_HOST="${NAME}-db"
    docker run -d --name "${NAME}-db" --network "$NETWORK" \
        -e MARIADB_ROOT_PASSWORD=root -e MARIADB_DATABASE=phpmyfaq \
        -e MARIADB_USER=phpmyfaq -e MARIADB_PASSWORD=phpmyfaq \
        mariadb:11 >/dev/null
fi

docker run -d --name "$NAME" --network "$NETWORK" -p "${PORT}:80" \
    -e PMF_DB_TYPE="$DB_TYPE" -e PMF_DB_HOST="$DB_HOST" \
    -e PMF_DB_USER=phpmyfaq -e PMF_DB_PASS=phpmyfaq \
    -e PMF_ADMIN_PASSWORD=smoke-test-password -e PMF_BASE_URL="$BASE_URL" \
    "$IMAGE" >/dev/null

echo "waiting for ${IMAGE} to become healthy ..."
i=0
while [ "$i" -lt 60 ]; do
    status=$(docker inspect --format '{{.State.Health.Status}}' "$NAME" 2>/dev/null || echo unknown)
    running=$(docker inspect --format '{{.State.Running}}' "$NAME" 2>/dev/null || echo false)
    [ "$running" = "true" ] || fail "container exited early"
    [ "$status" = "healthy" ] && break
    i=$((i + 1))
    sleep 3
done
[ "$status" = "healthy" ] || fail "container did not become healthy (status: ${status})"
echo "ok   container healthy"

docker logs "$NAME" 2>&1 | grep -q "Installation complete" || fail "headless installation did not run"
echo "ok   headless installation"

expect_status "/api/health" 200
expect_status "/" 200
# unauthenticated admin requests redirect to the login page
expect_status "/admin/" 302
expect_status "/assets/public/bootstrap-icons.css" 200
expect_status "/content/core/config/constants.php" 403
expect_status "/content/core/config/database.php" 403
expect_status "/content/user/attachments/.htaccess" 403
expect_status "/cache/routes/" 403
expect_status "/this-page-does-not-exist" 404

body=$(curl -s "${BASE_URL}/api/health")
[ "$body" = '{"status":"ok"}' ] || fail "unexpected health body: ${body}"
echo "ok   health body"

echo "smoke test passed for ${IMAGE}"

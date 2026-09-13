# 3. Production Deployment

This guide explains how to run phpMyFAQ in production with the official Docker images, using Docker Compose
directly or through Portainer.

## Table of Contents

- [Images](#images)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Deployment with Portainer](#deployment-with-portainer)
- [Architecture Options](#architecture-options)
- [HTTPS](#https)
- [Backup and Restore](#backup-and-restore)
- [Updates](#updates)
- [Monitoring and Troubleshooting](#monitoring-and-troubleshooting)
- [Running the image without Compose](#running-the-image-without-compose)
- [Building the image yourself](#building-the-image-yourself)

## Images

Every phpMyFAQ release publishes two images to the GitHub Container Registry:

| Image                                             | Web server              | Ports            |
|---------------------------------------------------|-------------------------|------------------|
| `ghcr.io/thorsten/phpmyfaq:<version>`             | Apache 2.4 + mod_php    | 80               |
| `ghcr.io/thorsten/phpmyfaq:<version>-frankenphp`  | FrankenPHP (Caddy)      | 80, 443, 443/udp |

`<version>` is the release number, e.g. `4.2.1`; the floating tags `4.2`, `4` and `latest` follow the
newest stable release, pre-releases only get their exact version. Nightly builds of the development
branch are published as `nightly` and `nightly-<date>` (also with the `-frankenphp` suffix); they are
untested snapshots, not for production. The images are public, no login is needed to pull them. Images are built for `linux/amd64` and `linux/arm64`, and contain the same payload as the
release archive: PHP 8.4, all required extensions, production dependencies and the built frontend assets.

The application writes only below `content/`. These directories are volumes in the compose file:

| Path in the container                    | Content                                             |
|------------------------------------------|-----------------------------------------------------|
| `/var/www/html/content/core/config`      | database and service configuration                  |
| `/var/www/html/content/core/data`        | application data (SQLite databases, exports)        |
| `/var/www/html/content/core/logs`        | application logs                                    |
| `/var/www/html/content/user/attachments` | uploaded attachments                                |
| `/var/www/html/content/user/images`      | uploaded images                                     |

## Prerequisites

- Docker Engine 24+ with the Compose plugin (`docker compose`, v2.20 or newer)
- 2 GB RAM for phpMyFAQ and its database; 4 GB or more when running Elasticsearch or OpenSearch
- A domain name and a reverse proxy or FrankenPHP for HTTPS

## Quick Start

1. **Get the deployment files** (the two files are all you need, no checkout required)
   ```bash
   mkdir phpmyfaq && cd phpmyfaq
   curl -fsSLO https://raw.githubusercontent.com/thorsten/phpMyFAQ/main/docker-compose.prod.yml
   curl -fsSL -o .env https://raw.githubusercontent.com/thorsten/phpMyFAQ/main/.env.production.example
   ```

2. **Edit `.env`**
   - choose the services in `COMPOSE_PROFILES`, e.g. `apache,mariadb`
   - set all passwords marked `change-me`
   - set `PMF_BASE_URL` to the public URL of your FAQ
   - pin `PMF_VERSION` to a release

3. **Start**
   ```bash
   docker compose -f docker-compose.prod.yml up -d
   docker compose -f docker-compose.prod.yml logs -f apache   # or frankenphp
   ```

4. **Log in** at `http://<host>/admin/` with `PMF_ADMIN_USER` and `PMF_ADMIN_PASSWORD`.

The container installs phpMyFAQ on its first start when `PMF_DB_HOST` and `PMF_ADMIN_PASSWORD` are set.
Leave `PMF_DB_HOST` empty to use the web installer at `http://<host>/setup/` instead; enter the service
name (`mariadb` or `postgres`) as the database host there.

## Configuration

All settings live in `.env`; `.env.production.example` documents every variable. The important ones:

| Variable                          | Purpose                                                                            |
|-----------------------------------|------------------------------------------------------------------------------------|
| `COMPOSE_PROFILES`                | which services run: one web server, one database, optionally one search engine     |
| `PMF_IMAGE`, `PMF_VERSION`        | image and tag                                                                      |
| `PMF_HTTP_PORT`, `PMF_HTTPS_PORT` | published ports                                                                    |
| `PMF_TIMEZONE`, `PMF_MEMORY_LIMIT`, `PHP_UPLOAD_MAX_FILESIZE`, `PHP_POST_MAX_SIZE` | PHP settings, applied on every start |
| `PMF_DB_*`, `PMF_ADMIN_*`, `PMF_BASE_URL` | headless installation, read only while no installation exists               |
| `MYSQL_*`, `POSTGRES_*`           | credentials the database container is created with; `PMF_DB_*` must match them     |
| `SERVER_NAME`                     | FrankenPHP only: a public host name turns on automatic HTTPS                        |
| `ELASTICSEARCH_BASE_URI`, `OPENSEARCH_BASE_URI` | search engine URL, e.g. `http://elasticsearch:9200`                   |

Values that are already stored in the installation (database credentials, base URL) are changed in the
administration or in the files of the `phpmyfaq_config` volume, not by editing `.env` afterwards.

## Deployment with Portainer

1. **Stacks → Add stack**, name it `phpmyfaq`.
2. Choose **Repository** with the URL `https://github.com/thorsten/phpMyFAQ`, reference `refs/heads/main`
   and compose path `docker-compose.prod.yml`, or paste the file into the web editor.
3. Under **Environment variables** add the variables from `.env.production.example`. `COMPOSE_PROFILES`
   is required, otherwise no service starts. The minimum is:
   ```
   COMPOSE_PROFILES=apache,mariadb
   PMF_VERSION=4.2.1
   PMF_BASE_URL=https://faq.example.com
   MYSQL_ROOT_PASSWORD=...
   MYSQL_PASSWORD=...
   PMF_DB_HOST=mariadb
   PMF_DB_PASS=...          # same value as MYSQL_PASSWORD
   PMF_ADMIN_PASSWORD=...
   ```
4. **Deploy the stack** and watch the container health under *Containers*. The web container reports
   healthy once the installation finished and `/api/health` answers.

Portainer stacks do not run `docker compose`'s profile expansion from a `.env` file, which is why
`COMPOSE_PROFILES` is set as a stack variable.

## Architecture Options

### Web server

- **Apache + mod_php** (`apache` profile) is the default. It serves plain HTTP on port 80 and expects a
  reverse proxy for TLS. URL rewriting comes from the shipped `.htaccess`.
- **FrankenPHP** (`frankenphp` profile) is a Caddy-based PHP application server with HTTP/2, HTTP/3 and
  built-in certificate management. With `SERVER_NAME=faq.example.com` it obtains Let's Encrypt
  certificates itself; ports 80 and 443 must be reachable from the internet for that. With the default
  `SERVER_NAME=:80` it behaves like the Apache image.

Nginx + PHP-FPM is not offered as an image: it needs a second container that shares the static files,
which has no clean equivalent with immutable images. Put nginx in front of the Apache or FrankenPHP
container as a reverse proxy instead.

### Database

- **MariaDB** (`mariadb` profile): `PMF_DB_TYPE=mysqli`, `PMF_DB_HOST=mariadb`.
- **PostgreSQL** (`postgres` profile): `PMF_DB_TYPE=pgsql`, `PMF_DB_HOST=postgres`.

Both listen on the internal network only. To use an existing database server instead, drop the database
profile and point `PMF_DB_HOST` at it.

### Search engine (optional)

- **Elasticsearch** (`elasticsearch` profile): set `ELASTICSEARCH_BASE_URI=http://elasticsearch:9200`.
- **OpenSearch** (`opensearch` profile): set `OPENSEARCH_BASE_URI=http://opensearch:9200`.

Enable the search engine in the administration afterwards. Both containers run without authentication
and are only reachable on the internal network. Elasticsearch needs `vm.max_map_count=262144` on the host:

```bash
echo "vm.max_map_count=262144" | sudo tee /etc/sysctl.d/99-elasticsearch.conf && sudo sysctl --system
```

## HTTPS

### Option 1: FrankenPHP with automatic certificates

Set `COMPOSE_PROFILES=frankenphp,...`, `SERVER_NAME=faq.example.com` and `PMF_BASE_URL=https://faq.example.com`.
Certificates are stored in the `caddy_data` volume.

### Option 2: A reverse proxy in front of the Apache image

Add a `docker-compose.override.yml` with your proxy. The example uses Traefik with Let's Encrypt:

```yaml
services:
  traefik:
    image: traefik:v3.2
    restart: unless-stopped
    command:
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.web.http.redirections.entrypoint.to=websecure"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.letsencrypt.acme.tlschallenge=true"
      - "--certificatesresolvers.letsencrypt.acme.email=you@example.com"
      - "--certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json"
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - traefik_letsencrypt:/letsencrypt
    networks:
      - phpmyfaq

  apache:
    ports: !override []
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.phpmyfaq.rule=Host(`faq.example.com`)"
      - "traefik.http.routers.phpmyfaq.entrypoints=websecure"
      - "traefik.http.routers.phpmyfaq.tls.certresolver=letsencrypt"
      - "traefik.http.services.phpmyfaq.loadbalancer.server.port=80"

volumes:
  traefik_letsencrypt:
```

Set `PMF_BASE_URL=https://faq.example.com` so that generated links use the public scheme and host.

## Backup and Restore

Back up the database and the five `phpmyfaq_*` volumes together. Stopping the web container first gives
a consistent snapshot.

```bash
#!/bin/bash
set -euo pipefail
BACKUP_DIR="/backup/phpmyfaq"
DATE=$(date +%Y%m%d_%H%M%S)
COMPOSE="docker compose -f docker-compose.prod.yml"
mkdir -p "$BACKUP_DIR"

# Database (MariaDB; for PostgreSQL use: docker exec phpmyfaq-postgres pg_dump -U phpmyfaq phpmyfaq)
docker exec phpmyfaq-mariadb sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' \
  | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Volumes (names are prefixed with the compose project name, "phpmyfaq" by default)
for volume in config data logs attachments images; do
  docker run --rm -v "phpmyfaq_phpmyfaq_${volume}:/data:ro" -v "$BACKUP_DIR:/backup" alpine \
    tar czf "/backup/${volume}_$DATE.tar.gz" -C /data .
done

find "$BACKUP_DIR" -name "*.gz" -mtime +7 -delete
echo "Backup completed: $DATE"
```

Restore by loading the dump into the database container and extracting each archive into its volume with
the same `docker run ... alpine tar xzf` pattern, then restart the web container. `docker volume ls`
shows the exact volume names of your project.

## Updates

```bash
./backup.sh
sed -i 's/^PMF_VERSION=.*/PMF_VERSION=4.2.2/' .env
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

After a version change, open `http://<host>/update/` or the administration; the updater migrates the
database schema when necessary.

## Monitoring and Troubleshooting

Every image has a `HEALTHCHECK` on `GET /api/health`. The endpoint answers `{"status":"ok"}` when the
application bootstrapped and reached its database, and it does not depend on the public API being enabled.

```bash
docker compose -f docker-compose.prod.yml ps                 # health column
docker inspect --format '{{json .State.Health}}' phpmyfaq-apache | jq
docker compose -f docker-compose.prod.yml logs --tail=100 apache
```

Common problems:

- **Nothing starts**: `COMPOSE_PROFILES` is empty. Set it in `.env` or pass `--profile`.
- **Headless installation failed**: the log shows the installer output. Typical causes are a database
  that was still initialising (the entrypoint retries for a minute), or `PMF_DB_PASS` not matching
  `MYSQL_PASSWORD` / `POSTGRES_PASSWORD`. The web installer at `/setup/` stays available.
- **Wrong links or redirects to the wrong host**: `PMF_BASE_URL` was wrong at installation time. Change
  the reference URL in the administration under *Configuration*.
- **Uploads fail**: raise `PHP_UPLOAD_MAX_FILESIZE` and `PHP_POST_MAX_SIZE` in `.env` and recreate the
  container.
- **Permissions**: the entrypoint gives the `content/` volumes to `www-data` on every start; run
  `docker exec phpmyfaq-apache chown -R www-data:www-data content` if files were added from outside.

## Running the image without Compose

```bash
docker run -d --name phpmyfaq -p 80:80 \
  -e PMF_DB_HOST=db.example.com -e PMF_DB_USER=phpmyfaq -e PMF_DB_PASS=secret \
  -e PMF_ADMIN_PASSWORD=secret -e PMF_BASE_URL=https://faq.example.com \
  -v phpmyfaq_config:/var/www/html/content/core/config \
  -v phpmyfaq_data:/var/www/html/content/core/data \
  -v phpmyfaq_logs:/var/www/html/content/core/logs \
  -v phpmyfaq_attachments:/var/www/html/content/user/attachments \
  -v phpmyfaq_images:/var/www/html/content/user/images \
  ghcr.io/thorsten/phpmyfaq:4.2.1
```

SQLite works for small installations: `-e PMF_DB_TYPE=sqlite3 -e PMF_DB_HOST=/var/www/html/content/core/data/phpmyfaq.sqlite`.

## Building the image yourself

```bash
docker build -f .docker/production/Dockerfile --target apache -t ghcr.io/thorsten/phpmyfaq:local .
docker build -f .docker/production/Dockerfile --target frankenphp -t ghcr.io/thorsten/phpmyfaq:local-frankenphp .
.docker/production/smoke-test.sh ghcr.io/thorsten/phpmyfaq:local
```

The GitHub Actions workflow `.github/workflows/docker-publish.yml` runs the same build and smoke test for
pull requests and publishes multi-platform images on every release. Publishing needs no secrets, the
registry accepts the workflow token. The package is created private on its first push; set it to public
once under the repository's *Packages* settings.

## Support and Resources

- **Official Website**: [https://www.phpmyfaq.de](https://www.phpmyfaq.de)
- **Official Documentation**: [https://www.phpmyfaq.de/docs](https://www.phpmyfaq.de/docs)
- **GitHub Repository**: [https://github.com/thorsten/phpMyFAQ](https://github.com/thorsten/phpMyFAQ)
- **GitHub Issues**: [https://github.com/thorsten/phpMyFAQ/issues](https://github.com/thorsten/phpMyFAQ/issues)
- **Community Forum**: [https://forum.phpmyfaq.de](https://forum.phpmyfaq.de)
- **Bluesky**: [@phpmyfaq.de](https://bsky.app/profile/phpmyfaq.de)
- **Discord**: [Join our Discord](https://discord.gg/MXX7rRte)

For paid customization and support services, visit our [support page](https://www.phpmyfaq.de/support).

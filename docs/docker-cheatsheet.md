# 4. Docker Compose Cheat Sheet

Quick reference for common Docker Compose operations with the phpMyFAQ production deployment described in
[Production Deployment](deployment.md).

The commands assume:

- the files `docker-compose.prod.yml` and `.env` are in a directory called `phpmyfaq`, so the Compose project name is
  `phpmyfaq`
- the profiles `apache` and `mariadb` are enabled in `COMPOSE_PROFILES`

The services and containers of `docker-compose.prod.yml` are:

| Profile         | Service         | Container                |
|-----------------|-----------------|--------------------------|
| `apache`        | `apache`        | `phpmyfaq-apache`        |
| `frankenphp`    | `frankenphp`    | `phpmyfaq-frankenphp`    |
| `mariadb`       | `mariadb`       | `phpmyfaq-mariadb`       |
| `postgres`      | `postgres`      | `phpmyfaq-postgres`      |
| `elasticsearch` | `elasticsearch` | `phpmyfaq-elasticsearch` |
| `opensearch`    | `opensearch`    | `phpmyfaq-opensearch`    |

If you use FrankenPHP or PostgreSQL, replace the names accordingly. Compose prefixes volume and network names with the
project name: the volume `phpmyfaq_data` is called `phpmyfaq_phpmyfaq_data`, the network `phpmyfaq` is called
`phpmyfaq_phpmyfaq`. `docker volume ls` shows the exact names.

To omit `-f docker-compose.prod.yml` in all commands, set it once in your shell:

```bash
export COMPOSE_FILE=docker-compose.prod.yml
```

## Basic Operations

### Start Services
```bash
# Start all services of the profiles in COMPOSE_PROFILES
docker compose -f docker-compose.prod.yml up -d

# Start specific service
docker compose -f docker-compose.prod.yml up -d apache

# Start with other profiles than in .env
docker compose -f docker-compose.prod.yml --profile frankenphp --profile postgres up -d
```

### Stop Services
```bash
# Stop all services
docker compose -f docker-compose.prod.yml stop

# Stop specific service
docker compose -f docker-compose.prod.yml stop apache

# Stop and remove containers
docker compose -f docker-compose.prod.yml down

# Stop and remove containers + volumes (DANGEROUS - deletes the FAQ data and the database!)
docker compose -f docker-compose.prod.yml down -v
```

### Restart Services
```bash
# Restart all services
docker compose -f docker-compose.prod.yml restart

# Restart specific service
docker compose -f docker-compose.prod.yml restart apache
```

## Monitoring

### View Status
```bash
# List all containers with their health status
docker compose -f docker-compose.prod.yml ps

# Show resource usage
docker stats

# Show container details
docker inspect phpmyfaq-apache
```

### View Logs
```bash
# All services (follow mode)
docker compose -f docker-compose.prod.yml logs -f

# Specific service
docker compose -f docker-compose.prod.yml logs -f apache

# Last 100 lines
docker compose -f docker-compose.prod.yml logs --tail=100 apache

# Logs of the last hour
docker compose -f docker-compose.prod.yml logs --since 1h

# Save logs to file
docker compose -f docker-compose.prod.yml logs > phpmyfaq-logs.txt
```

The web server writes its access and error logs to the container output. The application logs of phpMyFAQ are
stored in the `content/core/logs` directory (volume `phpmyfaq_logs`).

## Updates and Maintenance

### Update Images

Enable the maintenance mode and make a backup first, then open `/update/` after the containers were recreated. See
[Updates](deployment.md) in the deployment guide for the complete procedure.

```bash
# Pull the images of the version set in PMF_VERSION
docker compose -f docker-compose.prod.yml pull

# Update and restart
docker compose -f docker-compose.prod.yml pull && \
docker compose -f docker-compose.prod.yml up -d

# Force recreate containers
docker compose -f docker-compose.prod.yml up -d --force-recreate
```

`docker-compose.prod.yml` only uses published images, it does not build them. To build your own image, see
*Building the image yourself* in the deployment guide.

## Database Operations

### Database Backup
```bash
# MariaDB backup
docker exec phpmyfaq-mariadb sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' \
  > backup.sql

# PostgreSQL backup
docker exec phpmyfaq-postgres pg_dump -U phpmyfaq phpmyfaq > backup.sql

# Compressed backup
docker exec phpmyfaq-mariadb sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' \
  | gzip > backup.sql.gz
```

### Database Restore
```bash
# MariaDB restore
docker exec -i phpmyfaq-mariadb sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' \
  < backup.sql

# PostgreSQL restore
docker exec -i phpmyfaq-postgres psql -U phpmyfaq -d phpmyfaq < backup.sql

# From compressed backup
gunzip < backup.sql.gz | docker exec -i phpmyfaq-mariadb \
  sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
```

### Database Access
```bash
# MariaDB shell
docker exec -it phpmyfaq-mariadb mariadb -u phpmyfaq -p phpmyfaq

# PostgreSQL shell
docker exec -it phpmyfaq-postgres psql -U phpmyfaq -d phpmyfaq

# Execute SQL query
docker exec phpmyfaq-mariadb \
  sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" -e "SHOW TABLES;"'
```

The examples use the default user and database name `phpmyfaq`; adjust them if you changed `MYSQL_USER`,
`MYSQL_DATABASE`, `POSTGRES_USER`, or `POSTGRES_DB` in `.env`.

## Volume Management

| Volume                 | Path in the container                    |
|------------------------|------------------------------------------|
| `phpmyfaq_config`      | `/var/www/html/content/core/config`      |
| `phpmyfaq_data`        | `/var/www/html/content/core/data`        |
| `phpmyfaq_logs`        | `/var/www/html/content/core/logs`        |
| `phpmyfaq_attachments` | `/var/www/html/content/user/attachments` |
| `phpmyfaq_images`      | `/var/www/html/content/user/images`      |

The volumes `caddy_data` and `caddy_config` hold the certificates and the Caddy state of the FrankenPHP container,
`mariadb_data`, `postgres_data`, `elasticsearch_data`, and `opensearch_data` the files of the database and the search
engine. Back up the database with a dump instead of copying its volume.

### List Volumes
```bash
# List all volumes
docker volume ls

# Filter phpMyFAQ volumes
docker volume ls | grep phpmyfaq
```

### Inspect Volume
```bash
# View volume details
docker volume inspect phpmyfaq_phpmyfaq_data

# Find volume location
docker volume inspect --format '{{ .Mountpoint }}' phpmyfaq_phpmyfaq_data
```

### Backup Volumes
```bash
# Backup data volume
docker run --rm \
  -v phpmyfaq_phpmyfaq_data:/source:ro \
  -v "$(pwd)":/backup \
  alpine tar czf /backup/data-backup.tar.gz -C /source .

# Backup all phpMyFAQ volumes
mkdir -p backups
for vol in config data logs attachments images; do
  docker run --rm \
    -v "phpmyfaq_phpmyfaq_${vol}:/source:ro" \
    -v "$(pwd)/backups":/backup \
    alpine tar czf "/backup/${vol}-$(date +%Y%m%d).tar.gz" -C /source .
done
```

The complete backup script for the database and the volumes is part of the deployment guide, see
*Backup and Restore*.

### Restore Volumes
```bash
# Stop the web container first
docker compose -f docker-compose.prod.yml stop apache

# Restore data volume
docker run --rm \
  -v phpmyfaq_phpmyfaq_data:/target \
  -v "$(pwd)":/backup \
  alpine tar xzf /backup/data-backup.tar.gz -C /target

docker compose -f docker-compose.prod.yml start apache
```

### Remove Volumes
```bash
# Remove specific volume (DANGEROUS!)
docker volume rm phpmyfaq_phpmyfaq_data

# Remove unused anonymous volumes
docker volume prune
```

## Container Shell Access

### Execute Commands
```bash
# Open bash shell
docker exec -it phpmyfaq-apache bash

# Run command
docker exec phpmyfaq-apache ls -la /var/www/html

# Run as different user
docker exec -u www-data phpmyfaq-apache whoami

# Run the phpMyFAQ console, the mail queue worker or the task scheduler
docker exec -u www-data phpmyfaq-apache php /var/www/bin/console list
docker exec -u www-data phpmyfaq-apache php /var/www/bin/worker.php
docker exec -u www-data phpmyfaq-apache php /var/www/bin/scheduler.php run
```

### File Operations
```bash
# Copy file from container
docker cp phpmyfaq-apache:/var/www/html/content/core/config/database.php ./database.php

# Copy file to container
docker cp local-file.txt phpmyfaq-apache:/var/www/html/content/user/images/

# List the application logs
docker exec phpmyfaq-apache ls -la /var/www/html/content/core/logs
```

Only files below `content/` are kept in volumes. Changes to all other files are lost when the container is recreated.

### Check File Permissions
```bash
# List permissions
docker exec phpmyfaq-apache ls -la /var/www/html/content/core

# Fix permissions (the entrypoint also does this on every start)
docker exec phpmyfaq-apache chown -R www-data:www-data /var/www/html/content
```

## Network Operations

### List Networks
```bash
# List all networks
docker network ls

# Inspect network
docker network inspect phpmyfaq_phpmyfaq
```

### Network Diagnostics
```bash
# Check DNS resolution between containers
docker exec phpmyfaq-apache getent hosts mariadb

# Test database connection
docker exec phpmyfaq-apache php -r 'var_dump((bool) @fsockopen("mariadb", 3306));'

# Test search engine connection
docker exec phpmyfaq-apache curl -fsS http://elasticsearch:9200/_cluster/health
```

## Health Checks

### Check Container Health
```bash
# View health status
docker inspect --format '{{json .State.Health}}' phpmyfaq-apache

# Watch health status
watch -n 5 "docker inspect --format '{{.State.Health.Status}}' phpmyfaq-apache"

# All health statuses
docker compose -f docker-compose.prod.yml ps
```

### Manual Health Check
```bash
# Check web server and application (port PMF_HTTP_PORT, default 80)
curl -f http://localhost/api/health

# Check database
docker exec phpmyfaq-mariadb healthcheck.sh --connect --innodb_initialized

# Check Elasticsearch (not published to the host, so check from inside the container)
docker exec phpmyfaq-elasticsearch curl -fs http://localhost:9200/_cluster/health
```

## Cleanup

### Remove Stopped Containers
```bash
# Remove all stopped containers
docker container prune

# Remove specific stopped container
docker rm phpmyfaq-apache
```

### Clean Everything
```bash
# Check disk usage before cleanup
docker system df

# Remove unused data
docker system prune

# Remove all unused images and anonymous volumes as well (DANGEROUS!)
docker system prune -a --volumes
```

### Remove phpMyFAQ Stack Completely
```bash
# Stop and remove containers
docker compose -f docker-compose.prod.yml down

# Remove volumes (DANGEROUS - you'll lose all data!)
docker compose -f docker-compose.prod.yml down -v

# Remove images
docker rmi ghcr.io/thorsten/phpmyfaq:latest
docker rmi mariadb:11.8
docker rmi elasticsearch:8.16.5
```

## Troubleshooting Commands

### Container Issues
```bash
# Restart unhealthy container
docker compose -f docker-compose.prod.yml restart apache

# View container processes
docker top phpmyfaq-apache

# View container resource limits
docker inspect --format '{{json .HostConfig.Resources}}' phpmyfaq-apache

# Check exit code
docker inspect --format '{{.State.ExitCode}}' phpmyfaq-apache
```

### Check the Configuration
```bash
# Show the resolved compose configuration (with the values from .env)
docker compose -f docker-compose.prod.yml config

# Validate compose file
docker compose -f docker-compose.prod.yml config --quiet
```

### Performance Analysis
```bash
# Live resource monitoring
docker stats --no-stream

# Check container events
docker events --filter container=phpmyfaq-apache

# Analyze image layers
docker history ghcr.io/thorsten/phpmyfaq:latest
```

## Environment Variables

### View Environment
```bash
# Show all environment variables
docker exec phpmyfaq-apache env

# Search for specific variable
docker exec phpmyfaq-apache env | grep PMF_DB
```

The output contains passwords, so don't save it to files or share it.

### Update Environment
```bash
# Edit .env file
nano .env

# Recreate containers with new env
docker compose -f docker-compose.prod.yml up -d --force-recreate
```

The `PMF_DB_*`, `PMF_ADMIN_*`, and `PMF_BASE_URL` variables are only read for the headless installation. Changing them
later has no effect on an existing installation.

## Advanced Operations

### Export/Import Images
```bash
# Export image
docker save ghcr.io/thorsten/phpmyfaq:latest | gzip > phpmyfaq-image.tar.gz

# Import image
gunzip < phpmyfaq-image.tar.gz | docker load

# Transfer between servers
docker save ghcr.io/thorsten/phpmyfaq:latest | ssh user@server 'docker load'
```

## Useful Aliases

Add to `~/.bashrc` or `~/.zshrc`:

```bash
# phpMyFAQ Docker aliases
alias pmf-up='docker compose -f docker-compose.prod.yml up -d'
alias pmf-down='docker compose -f docker-compose.prod.yml down'
alias pmf-restart='docker compose -f docker-compose.prod.yml restart'
alias pmf-logs='docker compose -f docker-compose.prod.yml logs -f'
alias pmf-ps='docker compose -f docker-compose.prod.yml ps'
alias pmf-update='docker compose -f docker-compose.prod.yml pull && docker compose -f docker-compose.prod.yml up -d'
alias pmf-shell='docker exec -it phpmyfaq-apache bash'
alias pmf-db='docker exec -it phpmyfaq-mariadb mariadb -u phpmyfaq -p phpmyfaq'
alias pmf-backup='./backup.sh'   # the backup script from the deployment guide
```

## Environment-Specific Tips

### Portainer

Portainer deploys the stack from its web interface, see *Deployment with Portainer* in the deployment guide. Set
`COMPOSE_PROFILES` as a stack environment variable there.

### Docker Desktop
- Use the GUI to view containers, logs, and stats
- Access terminal directly from container list
- Volume management in the Volumes section

### Production Server
```bash
# Start Docker on boot; the containers come back thanks to "restart: unless-stopped"
sudo systemctl enable docker
sudo systemctl start docker
```

## Quick Diagnostic Checklist

When something goes wrong:

1. **Check if containers are running**
   ```bash
   docker compose -f docker-compose.prod.yml ps
   ```

2. **Check logs for errors**
   ```bash
   docker compose -f docker-compose.prod.yml logs --tail=50
   ```

3. **Verify network connectivity**
   ```bash
   docker exec phpmyfaq-apache getent hosts mariadb
   ```

4. **Check disk space**
   ```bash
   docker system df
   ```

5. **Verify environment variables**
   ```bash
   docker exec phpmyfaq-apache env | grep PMF
   ```

6. **Test database connection**
   ```bash
   docker exec phpmyfaq-mariadb healthcheck.sh --connect
   ```

7. **Check file permissions**
   ```bash
   docker exec phpmyfaq-apache ls -la /var/www/html/content/core
   ```

8. **Review resource usage**
   ```bash
   docker stats --no-stream
   ```

See *Monitoring and Troubleshooting* in the deployment guide for the common problems and their solutions.

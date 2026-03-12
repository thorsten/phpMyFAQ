# 4. Docker Compose Cheat Sheet

Quick reference for common Docker Compose operations with phpMyFAQ production deployment.

## Basic Operations

### Start Services
```bash
# Start all services
docker-compose -f docker-compose.prod.yml up -d

# Start specific service
docker-compose -f docker-compose.prod.yml up -d apache

# Start with build
docker-compose -f docker-compose.prod.yml up -d --build
```

### Stop Services
```bash
# Stop all services
docker-compose -f docker-compose.prod.yml stop

# Stop specific service
docker-compose -f docker-compose.prod.yml stop apache

# Stop and remove containers
docker-compose -f docker-compose.prod.yml down

# Stop and remove containers + volumes (DANGEROUS!)
docker-compose -f docker-compose.prod.yml down -v
```

### Restart Services
```bash
# Restart all services
docker-compose -f docker-compose.prod.yml restart

# Restart specific service
docker-compose -f docker-compose.prod.yml restart apache
```

## Monitoring

### View Status
```bash
# List all containers
docker-compose -f docker-compose.prod.yml ps

# Show resource usage
docker stats

# Show container details
docker inspect phpmyfaq-apache
```

### View Logs
```bash
# All services (follow mode)
docker-compose -f docker-compose.prod.yml logs -f

# Specific service
docker-compose -f docker-compose.prod.yml logs -f apache

# Last 100 lines
docker-compose -f docker-compose.prod.yml logs --tail=100 apache

# Logs since specific time
docker-compose -f docker-compose.prod.yml logs --since 2024-01-01T00:00:00

# Save logs to file
docker-compose -f docker-compose.prod.yml logs > phpmyfaq-logs.txt
```

## Updates and Maintenance

### Update Images
```bash
# Pull latest images
docker-compose -f docker-compose.prod.yml pull

# Update and restart
docker-compose -f docker-compose.prod.yml pull && \
docker-compose -f docker-compose.prod.yml up -d

# Force recreate containers
docker-compose -f docker-compose.prod.yml up -d --force-recreate
```

### Rebuild Services
```bash
# Rebuild all
docker-compose -f docker-compose.prod.yml build

# Rebuild specific service
docker-compose -f docker-compose.prod.yml build apache

# Rebuild without cache
docker-compose -f docker-compose.prod.yml build --no-cache
```

## Database Operations

### Database Backup
```bash
# MariaDB backup
docker exec phpmyfaq-mariadb mysqldump \
  -u phpmyfaq -p[PASSWORD] phpmyfaq > backup.sql

# PostgreSQL backup
docker exec phpmyfaq-postgres pg_dump \
  -U phpmyfaq phpmyfaq > backup.sql

# Compressed backup
docker exec phpmyfaq-mariadb mysqldump \
  -u phpmyfaq -p[PASSWORD] phpmyfaq | gzip > backup.sql.gz
```

### Database Restore
```bash
# MariaDB restore
cat backup.sql | docker exec -i phpmyfaq-mariadb \
  mysql -u phpmyfaq -p[PASSWORD] phpmyfaq

# PostgreSQL restore
cat backup.sql | docker exec -i phpmyfaq-postgres \
  psql -U phpmyfaq -d phpmyfaq

# From compressed backup
gunzip < backup.sql.gz | docker exec -i phpmyfaq-mariadb \
  mysql -u phpmyfaq -p[PASSWORD] phpmyfaq
```

### Database Access
```bash
# MariaDB shell
docker exec -it phpmyfaq-mariadb mysql -u phpmyfaq -p

# PostgreSQL shell
docker exec -it phpmyfaq-postgres psql -U phpmyfaq -d phpmyfaq

# Execute SQL query
docker exec phpmyfaq-mariadb mysql -u phpmyfaq -p[PASSWORD] \
  -e "SHOW TABLES;" phpmyfaq
```

## Volume Management

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
docker volume inspect phpmyfaq_data

# Find volume location
docker volume inspect phpmyfaq_data | grep Mountpoint
```

### Backup Volumes
```bash
# Backup data volume
docker run --rm \
  -v phpmyfaq_data:/source \
  -v $(pwd):/backup \
  alpine tar czf /backup/data-backup.tar.gz -C /source .

# Backup all important volumes
for vol in data images attachments config; do
  docker run --rm \
    -v phpmyfaq_$vol:/source \
    -v $(pwd)/backups:/backup \
    alpine tar czf /backup/$vol-$(date +%Y%m%d).tar.gz -C /source .
done
```

### Restore Volumes
```bash
# Restore data volume
docker run --rm \
  -v phpmyfaq_data:/target \
  -v $(pwd):/backup \
  alpine tar xzf /backup/data-backup.tar.gz -C /target
```

### Remove Volumes
```bash
# Remove specific volume (DANGEROUS!)
docker volume rm phpmyfaq_data

# Remove all unused volumes
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
```

### File Operations
```bash
# Copy file from container
docker cp phpmyfaq-apache:/var/www/html/config.php ./config.php

# Copy file to container
docker cp local-file.txt phpmyfaq-apache:/var/www/html/

# View file content
docker exec phpmyfaq-apache cat /var/www/html/data/logs/error.log

# Edit file (if nano/vi installed)
docker exec -it phpmyfaq-apache nano /var/www/html/config.php
```

### Check File Permissions
```bash
# List permissions
docker exec phpmyfaq-apache ls -la /var/www/html/data

# Fix permissions
docker exec phpmyfaq-apache chown -R www-data:www-data /var/www/html/data
docker exec phpmyfaq-apache chmod -R 755 /var/www/html/data
```

## Network Operations

### List Networks
```bash
# List all networks
docker network ls

# Inspect network
docker network inspect phpmyfaq_phpmyfaq-network
```

### Network Diagnostics
```bash
# Test connectivity between containers
docker exec phpmyfaq-apache ping mariadb

# Check DNS resolution
docker exec phpmyfaq-apache nslookup mariadb

# Test database connection
docker exec phpmyfaq-apache nc -zv mariadb 3306
```

## Health Checks

### Check Container Health
```bash
# View health status
docker inspect phpmyfaq-apache | grep -A 10 Health

# Watch health status
watch -n 5 'docker inspect phpmyfaq-apache | grep -A 10 Health'

# All health statuses
docker-compose -f docker-compose.prod.yml ps | grep "health"
```

### Manual Health Check
```bash
# Check web server
curl -f http://localhost/api/health

# Check database
docker exec phpmyfaq-mariadb mysqladmin ping -u phpmyfaq -p[PASSWORD]

# Check Elasticsearch
curl http://localhost:9200/_cluster/health
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
# Remove unused data
docker system prune

# Remove everything (including volumes - DANGEROUS!)
docker system prune -a --volumes

# Check disk usage before cleanup
docker system df
```

### Remove phpMyFAQ Stack Completely
```bash
# Stop and remove containers
docker-compose -f docker-compose.prod.yml down

# Remove volumes (DANGEROUS - you'll lose all data!)
docker-compose -f docker-compose.prod.yml down -v

# Remove images
docker rmi phpmyfaq/phpmyfaq:latest
docker rmi mariadb:11.6
docker rmi elasticsearch:8.16.5
```

## Troubleshooting Commands

### Container Issues
```bash
# Restart unhealthy container
docker-compose -f docker-compose.prod.yml restart apache

# View container processes
docker top phpmyfaq-apache

# View container resource limits
docker inspect phpmyfaq-apache | grep -A 20 HostConfig

# Check exit code
docker inspect phpmyfaq-apache | grep ExitCode
```

### Debug Mode
```bash
# Run with debug output
docker-compose -f docker-compose.prod.yml --verbose up

# Check compose config
docker-compose -f docker-compose.prod.yml config

# Validate compose file
docker-compose -f docker-compose.prod.yml config --quiet
```

### Performance Analysis
```bash
# Live resource monitoring
docker stats --no-stream

# Check container events
docker events --filter container=phpmyfaq-apache

# Analyze image layers
docker history phpmyfaq/phpmyfaq:latest
```

## Environment Variables

### View Environment
```bash
# Show all environment variables
docker exec phpmyfaq-apache env

# Search for specific variable
docker exec phpmyfaq-apache env | grep PMF_DB

# Export to file
docker exec phpmyfaq-apache env > container-env.txt
```

### Update Environment
```bash
# Edit .env file
nano .env

# Recreate containers with new env
docker-compose -f docker-compose.prod.yml up -d --force-recreate
```

## Advanced Operations

### Docker Swarm (Production Cluster)
```bash
# Initialize swarm
docker swarm init

# Deploy stack
docker stack deploy -c docker-compose.prod.yml phpmyfaq

# List services
docker stack services phpmyfaq

# Scale service
docker service scale phpmyfaq_apache=3

# Remove stack
docker stack rm phpmyfaq
```

### Create Custom Network
```bash
# Create network
docker network create --driver bridge phpmyfaq-custom

# Connect container to network
docker network connect phpmyfaq-custom phpmyfaq-apache
```

### Export/Import Images
```bash
# Export image
docker save phpmyfaq/phpmyfaq:latest | gzip > phpmyfaq-image.tar.gz

# Import image
gunzip < phpmyfaq-image.tar.gz | docker load

# Transfer between servers
docker save phpmyfaq/phpmyfaq:latest | ssh user@server 'docker load'
```

## Useful Aliases

Add to `~/.bashrc` or `~/.zshrc`:

```bash
# phpMyFAQ Docker aliases
alias pmf-up='docker-compose -f docker-compose.prod.yml up -d'
alias pmf-down='docker-compose -f docker-compose.prod.yml down'
alias pmf-restart='docker-compose -f docker-compose.prod.yml restart'
alias pmf-logs='docker-compose -f docker-compose.prod.yml logs -f'
alias pmf-ps='docker-compose -f docker-compose.prod.yml ps'
alias pmf-update='docker-compose -f docker-compose.prod.yml pull && docker-compose -f docker-compose.prod.yml up -d'
alias pmf-shell='docker exec -it phpmyfaq-apache bash'
alias pmf-db='docker exec -it phpmyfaq-mariadb mysql -u phpmyfaq -p'
alias pmf-backup='./backup.sh'
```

## Environment-Specific Tips

### Portainer
```bash
# Deploy via Portainer CLI
portainer stack deploy phpmyfaq \
  --compose-file docker-compose.prod.yml \
  --env-file .env

# List stacks
portainer stack ls

# Remove stack
portainer stack rm phpmyfaq
```

### Docker Desktop
- Use the GUI to view containers, logs, and stats
- Access terminal directly from container list
- Volume management in the Volumes section

### Production Server
```bash
# Run in background with restart policy
docker-compose -f docker-compose.prod.yml up -d --no-deps apache

# Monitor with systemd service
sudo systemctl enable docker
sudo systemctl start docker
```

## Quick Diagnostic Checklist

When something goes wrong:

1. **Check if containers are running**
   ```bash
   docker-compose -f docker-compose.prod.yml ps
   ```

2. **Check logs for errors**
   ```bash
   docker-compose -f docker-compose.prod.yml logs --tail=50
   ```

3. **Verify network connectivity**
   ```bash
   docker exec phpmyfaq-apache ping mariadb
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
   docker exec phpmyfaq-mariadb mysqladmin ping
   ```

7. **Check file permissions**
   ```bash
   docker exec phpmyfaq-apache ls -la /var/www/html/data
   ```

8. **Review resource usage**
   ```bash
   docker stats --no-stream
   ```

---

**Pro Tip**: Replace `docker-compose.prod.yml` with `-f docker-compose.prod.yml` shorthand or create an alias!
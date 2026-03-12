# 3. Production Deployment

This guide explains how to deploy phpMyFAQ in production using Docker Compose with Portainer or standalone.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Deployment with Portainer](#deployment-with-portainer)
- [Manual Docker Compose Deployment](#manual-docker-compose-deployment)
- [Architecture Options](#architecture-options)
- [SSL/TLS Configuration](#ssltls-configuration)
- [Backup and Restore](#backup-and-restore)
- [Monitoring and Maintenance](#monitoring-and-maintenance)
- [Troubleshooting](#troubleshooting)

## Prerequisites

- Docker Engine 20.10+ and Docker Compose 2.0+
- At least 4GB RAM (8GB+ recommended for production with search)
- 20GB+ disk space
- Domain name (optional, for SSL/HTTPS)
- Basic understanding of Docker and networking

## Quick Start

1. **Clone the repository or download the production files**
   ```bash
   git clone https://github.com/thorsten/phpMyFAQ.git
   cd phpMyFAQ
   ```

2. **Create and configure your environment file**
   ```bash
   cp .env.production.example .env
   nano .env  # or use your preferred editor
   ```

3. **Configure your deployment**
   - Set strong passwords for all database credentials
   - Choose your database (MariaDB or PostgreSQL)
   - Choose your web server (Apache, Nginx+PHP-FPM, or FrankenPHP)
   - Configure timezone and other settings

4. **Deploy**
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

5. **Access phpMyFAQ**
   - Open your browser to `http://your-server-ip`
   - Complete the installation wizard

## Deployment with Portainer

### Method 1: Using Portainer Stacks (Recommended)

1. **Login to Portainer**
   - Navigate to `https://your-portainer-instance:9443`
   - Login with your credentials

2. **Create a new Stack**
   - Go to **Stacks** → **Add stack**
   - Give it a name: `phpmyfaq`

3. **Upload or paste the docker-compose file**
   - **Option A**: Use the Web editor
     - Paste the contents of `docker-compose.prod.yml`

   - **Option B**: Use Git repository
     - Repository URL: `https://github.com/thorsten/phpMyFAQ`
     - Compose path: `docker-compose.prod.yml`
     - Enable automatic updates (optional)

4. **Configure Environment Variables**
   - Click on **Environment variables** tab
   - Add variables from `.env.production.example`:
     ```
     PMF_DB_TYPE=mysqli
     PMF_DB_HOST=mariadb
     PMF_DB_NAME=phpmyfaq
     PMF_DB_USER=phpmyfaq
     PMF_DB_PASS=your_secure_password
     MYSQL_ROOT_PASSWORD=your_secure_root_password
     MYSQL_PASSWORD=your_secure_password
     PMF_TIMEZONE=UTC
     PMF_MEMORY_LIMIT=512M
     ```

5. **Edit the compose file** (in Portainer editor)
   - Choose ONE database service (comment out the other)
   - Choose ONE web server service (comment out the others)
   - Adjust resource limits if needed

6. **Deploy the stack**
   - Click **Deploy the stack**
   - Wait for all services to start (check logs if issues occur)

7. **Verify deployment**
   - Go to **Containers** to see running services
   - Check the health status of each container
   - Access the application at the configured port

### Method 2: Using Portainer Custom Templates

1. **Create a Custom Template**
   - Go to **App Templates** → **Custom Templates**
   - Create a new template
   - Name: `phpMyFAQ Production`
   - Platform: `Linux`
   - Type: `Stack`

2. **Paste the docker-compose.prod.yml content**

3. **Define template variables**
   - Add all environment variables from `.env.production.example`
   - Set descriptions for each variable

4. **Save and Deploy**
   - Templates can now be reused for multiple deployments

## Manual Docker Compose Deployment

### Standard Deployment

```bash
# 1. Prepare environment
cp .env.production.example .env
nano .env  # Configure all variables

# 2. Edit docker-compose.prod.yml
nano docker-compose.prod.yml
# - Choose your database service
# - Choose your web server
# - Comment out unused services

# 3. Deploy
docker-compose -f docker-compose.prod.yml up -d

# 4. Check status
docker-compose -f docker-compose.prod.yml ps
docker-compose -f docker-compose.prod.yml logs -f

# 5. Stop/Update
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml pull
docker-compose -f docker-compose.prod.yml up -d
```

### Using Docker Stack (Swarm Mode)

```bash
# 1. Initialize Swarm (if not already)
docker swarm init

# 2. Deploy stack
docker stack deploy -c docker-compose.prod.yml phpmyfaq

# 3. Check status
docker stack services phpmyfaq
docker stack ps phpmyfaq

# 4. Remove stack
docker stack rm phpmyfaq
```

## Architecture Options

### Database Options

#### Option 1: MariaDB (Recommended)
- Best compatibility with MySQL
- Excellent performance
- Wide community support

**Configuration:**
- Keep `mariadb` service enabled
- Comment out `postgres` service
- Set `PMF_DB_TYPE=mysqli`
- Set `PMF_DB_HOST=mariadb`

#### Option 2: PostgreSQL
- Advanced features
- Better for complex queries
- ACID compliance

**Configuration:**
- Keep `postgres` service enabled
- Comment out `mariadb` service
- Set `PMF_DB_TYPE=pgsql`
- Set `PMF_DB_HOST=postgres`

### Web Server Options

#### Option 1: Apache + mod_php (Default)
- Simple, widely used
- Good for most use cases
- Easy to configure

**Pros:**
- Simple setup
- Well-documented
- .htaccess support

**Cons:**
- Slightly higher memory usage
- No HTTP/3 support

#### Option 2: Nginx + PHP-FPM
- Better performance under high load
- Lower memory footprint
- More granular control

**Pros:**
- Excellent performance
- Better resource usage
- Advanced caching options

**Cons:**
- More complex configuration
- No .htaccess support (use nginx.conf)

#### Option 3: FrankenPHP
- Modern PHP application server
- HTTP/2, HTTP/3 support
- Built-in HTTPS with automatic certificates

**Pros:**
- Best performance
- Modern HTTP protocols
- Automatic HTTPS
- Built-in Caddy server

**Cons:**
- Newer technology
- Smaller community

### Search Engine Options

#### Elasticsearch
- Powerful full-text search
- Better for larger deployments
- More features

**Resource Requirements:**
- Minimum: 1GB RAM
- Recommended: 2GB+ RAM

#### OpenSearch
- Open-source Elasticsearch alternative
- Compatible with Elasticsearch
- Similar features

**Configuration:**
- Set `ELASTICSEARCH_BASE_URI` or `OPENSEARCH_BASE_URI`
- Choose one search engine
- Optional: can run without search engine

## SSL/TLS Configuration

### Option 1: Let's Encrypt with Traefik Reverse Proxy

Create a `docker-compose.override.yml`:

```yaml
version: '3.8'

services:
  traefik:
    image: traefik:v3.2
    command:
      - "--providers.docker=true"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.letsencrypt.acme.tlschallenge=true"
      - "--certificatesresolvers.letsencrypt.acme.email=your-email@example.com"
      - "--certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json"
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
      - traefik_letsencrypt:/letsencrypt
    networks:
      - phpmyfaq-network

  apache:
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.phpmyfaq.rule=Host(`your-domain.com`)"
      - "traefik.http.routers.phpmyfaq.entrypoints=websecure"
      - "traefik.http.routers.phpmyfaq.tls.certresolver=letsencrypt"
    ports: []  # Remove direct port exposure

volumes:
  traefik_letsencrypt:
```

### Option 2: Custom SSL Certificates

1. **Prepare certificates**
   ```bash
   mkdir -p ssl
   # Copy your certificate files
   cp your-cert.pem ssl/cert.pem
   cp your-key.pem ssl/cert-key.pem
   chmod 644 ssl/cert.pem
   chmod 600 ssl/cert-key.pem
   ```

2. **Update docker-compose.prod.yml**
   - Uncomment SSL volume mounts in your chosen web server service

### Option 3: FrankenPHP Automatic HTTPS

If using FrankenPHP:
- Set `SERVER_NAME=your-domain.com` in .env
- FrankenPHP will automatically obtain Let's Encrypt certificates
- No additional configuration needed

## Backup and Restore

### Automated Backup Script

Create `backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/backup/phpmyfaq"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Backup database
docker exec phpmyfaq-mariadb mysqldump -u phpmyfaq -p$MYSQL_PASSWORD phpmyfaq | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup volumes
docker run --rm -v phpmyfaq_data:/data -v $BACKUP_DIR:/backup alpine tar czf /backup/data_$DATE.tar.gz -C /data .
docker run --rm -v phpmyfaq_images:/images -v $BACKUP_DIR:/backup alpine tar czf /backup/images_$DATE.tar.gz -C /images .
docker run --rm -v phpmyfaq_attachments:/attachments -v $BACKUP_DIR:/backup alpine tar czf /backup/attachments_$DATE.tar.gz -C /attachments .
docker run --rm -v phpmyfaq_config:/config -v $BACKUP_DIR:/backup alpine tar czf /backup/config_$DATE.tar.gz -C /config .

# Remove old backups (keep last 7 days)
find "$BACKUP_DIR" -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

### Restore from Backup

```bash
#!/bin/bash
BACKUP_FILE="$1"

# Restore database
gunzip < db_backup.sql.gz | docker exec -i phpmyfaq-mariadb mysql -u phpmyfaq -p$MYSQL_PASSWORD phpmyfaq

# Restore volumes
docker run --rm -v phpmyfaq_data:/data -v /backup:/backup alpine tar xzf /backup/data_backup.tar.gz -C /data

echo "Restore completed"
```

### Schedule Regular Backups

Add to crontab:
```bash
# Daily backup at 2 AM
0 2 * * * /path/to/backup.sh >> /var/log/phpmyfaq-backup.log 2>&1
```

## Monitoring and Maintenance

### Health Checks

Check container health:
```bash
docker-compose -f docker-compose.prod.yml ps
docker inspect phpmyfaq-apache | grep -A 10 Health
```

### Log Management

View logs:
```bash
# All services
docker-compose -f docker-compose.prod.yml logs -f

# Specific service
docker-compose -f docker-compose.prod.yml logs -f apache

# Last 100 lines
docker-compose -f docker-compose.prod.yml logs --tail=100
```

Configure log rotation in `/etc/docker/daemon.json`:
```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
```

### Update phpMyFAQ

```bash
# 1. Backup first!
./backup.sh

# 2. Pull new images
docker-compose -f docker-compose.prod.yml pull

# 3. Recreate containers
docker-compose -f docker-compose.prod.yml up -d

# 4. Verify
docker-compose -f docker-compose.prod.yml ps
```

### Resource Monitoring

Monitor resource usage:
```bash
# Container stats
docker stats

# Disk usage
docker system df

# Cleanup
docker system prune -a --volumes  # WARNING: removes unused data
```

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker-compose -f docker-compose.prod.yml logs service-name

# Common issues:
# - Port already in use: Change ports in .env
# - Permission issues: Check volume permissions
# - Out of memory: Increase Docker memory limit
```

### Database Connection Failed

```bash
# Check database is running
docker-compose -f docker-compose.prod.yml ps mariadb

# Test connection
docker exec phpmyfaq-mariadb mysql -u phpmyfaq -p$MYSQL_PASSWORD -e "SELECT 1"

# Verify credentials in .env match database
```

### Application Errors

```bash
# Check PHP logs
docker-compose -f docker-compose.prod.yml logs apache | grep -i error

# Check permissions
docker exec phpmyfaq-apache ls -la /var/www/html/data

# Reset permissions
docker exec phpmyfaq-apache chown -R www-data:www-data /var/www/html/data
```

### Performance Issues

1. **Check resource usage**
   ```bash
   docker stats
   ```

2. **Adjust resource limits** in docker-compose.prod.yml

3. **Enable caching** (Redis, Memcached)

4. **Optimize database** (indexes, query cache)

5. **Use CDN** for static assets

### Elasticsearch Won't Start

```bash
# Increase vm.max_map_count on host
sudo sysctl -w vm.max_map_count=262144

# Make permanent
echo "vm.max_map_count=262144" | sudo tee -a /etc/sysctl.conf
```

## Security Best Practices

1. **Change all default passwords** before deploying
2. **Use strong passwords** (minimum 16 characters, mixed case, numbers, symbols)
3. **Don't expose database ports** to the host
4. **Use SSL/TLS** for all connections
5. **Keep software updated** regularly
6. **Enable firewall** rules
7. **Use Docker secrets** for sensitive data (Swarm mode)
8. **Regular security audits** and vulnerability scans
9. **Implement rate limiting** (e.g., with Nginx)
10. **Monitor logs** for suspicious activity

## Support and Resources

- **Official Website**: [https://www.phpmyfaq.de](https://www.phpmyfaq.de)
- **Official Documentation**: [https://www.phpmyfaq.de/docs](https://www.phpmyfaq.de/docs)
- **GitHub Repository**: [https://github.com/thorsten/phpMyFAQ](https://github.com/thorsten/phpMyFAQ)
- **GitHub Issues**: [https://github.com/thorsten/phpMyFAQ/issues](https://github.com/thorsten/phpMyFAQ/issues)
- **Community Forum**: [https://forum.phpmyfaq.de](https://forum.phpmyfaq.de)
- **Bluesky**: [@phpmyfaq.de](https://bsky.app/profile/phpmyfaq.de)
- **Discord**: [Join our Discord](https://discord.gg/MXX7rRte)

For paid customization and support services, visit our [support page](https://www.phpmyfaq.de/support).

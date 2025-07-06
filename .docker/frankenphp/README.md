# FrankenPHP Support for phpMyFAQ

This directory contains Docker configuration files for running phpMyFAQ with FrankenPHP, a modern PHP application server built on Caddy.

## Files

- `Dockerfile` - FrankenPHP Docker image with all required PHP extensions
- `Caddyfile` - Caddy web server configuration with FrankenPHP integration
- `docker-entrypoint.sh` - Entry point script for container initialization
- `frankenphp-worker.php` - Worker script for FrankenPHP worker mode (optional)

## Quick Start

### Using Docker Compose

```bash
# Start FrankenPHP service
docker-compose up frankenphp

# Access phpMyFAQ at http://localhost:8888 or https://localhost:8443
```

### Using Docker directly

```bash
# Build the image
docker build -t phpmyfaq-frankenphp -f .docker/frankenphp/Dockerfile .

# Run the container
docker run -p 8888:80 -v $(pwd)/phpmyfaq:/var/www/html phpmyfaq-frankenphp
```

## Features

- **High Performance**: Optional worker mode for keeping PHP code in memory
- **Built-in HTTPS**: Automatic SSL certificate management (disabled by default for development)
- **Modern Architecture**: Built on Caddy web server with HTTP/2 and HTTP/3 support
- **All PHP Extensions**: Includes all extensions required by phpMyFAQ
- **Security Headers**: Pre-configured security headers
- **URL Rewriting**: Automatic URL rewriting for clean URLs

## Configuration

### Worker Mode

Worker mode is disabled by default to avoid issues during initial setup. To enable it:

1. Ensure composer dependencies are installed
2. Uncomment the worker configuration in the Caddyfile
3. Restart the container

### Environment Variables

All standard phpMyFAQ environment variables are supported:

- `PMF_DB_HOST` - Database host
- `PMF_DB_NAME` - Database name
- `PMF_DB_USER` - Database user
- `PMF_DB_PASS` - Database password
- `PMF_TIMEZONE` - PHP timezone
- `PMF_MEMORY_LIMIT` - PHP memory limit
- `PHP_LOG_ERRORS` - Enable PHP error logging
- `PHP_ERROR_REPORTING` - PHP error reporting level

## Benefits over Traditional Setup

1. **Better Performance**: Worker mode eliminates PHP initialization overhead
2. **Simpler Deployment**: Single binary with built-in web server
3. **Automatic HTTPS**: No need for reverse proxy configuration
4. **Modern Features**: HTTP/2, HTTP/3, Server-Sent Events support
5. **Easy Configuration**: Simple Caddyfile syntax vs complex Apache/nginx configs

## Production Use

For production deployment:

1. Enable HTTPS in the Caddyfile (`auto_https on`)
2. Configure proper domain names
3. Enable worker mode for better performance
4. Adjust memory limits and other PHP settings as needed
5. Consider using a reverse proxy for load balancing

## Support

For issues specific to FrankenPHP integration, please check:
- [FrankenPHP Documentation](https://frankenphp.dev/docs/)
- [Caddy Documentation](https://caddyserver.com/docs/)
- [phpMyFAQ Documentation](https://www.phpmyfaq.de/docs/)
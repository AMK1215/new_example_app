# Deployment Guide

This guide covers deploying AMKSocial to production environments including server setup, configuration, and maintenance.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Server Requirements](#server-requirements)
- [Environment Setup](#environment-setup)
- [Database Configuration](#database-configuration)
- [Web Server Configuration](#web-server-configuration)
- [SSL Certificate Setup](#ssl-certificate-setup)
- [WebSocket Configuration](#websocket-configuration)
- [File Storage Setup](#file-storage-setup)
- [Process Management](#process-management)
- [Monitoring and Maintenance](#monitoring-and-maintenance)
- [Performance Optimization](#performance-optimization)
- [Backup Strategy](#backup-strategy)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### Server Requirements

**Minimum Requirements:**
- CPU: 2 cores
- RAM: 4GB
- Storage: 50GB SSD
- Network: 100 Mbps

**Recommended Requirements:**
- CPU: 4+ cores
- RAM: 8GB+
- Storage: 100GB+ SSD
- Network: 1 Gbps

### Software Requirements

**Operating System:**
- Ubuntu 20.04 LTS or later (recommended)
- CentOS 8+ or RHEL 8+
- Debian 10+

**Runtime:**
- PHP 8.2 or later
- Node.js 18.x or later
- Composer 2.x
- npm 9.x or later

**Database:**
- PostgreSQL 13+ (recommended)
- MySQL 8.0+
- MariaDB 10.5+

**Web Server:**
- Nginx 1.18+ (recommended)
- Apache 2.4+

**Additional Services:**
- Redis 6.0+ (for caching and sessions)
- Supervisor (for process management)
- Certbot (for SSL certificates)

## Environment Setup

### 1. Initial Server Setup

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install basic dependencies
sudo apt install -y curl wget git unzip software-properties-common

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-pgsql \
    php8.2-sqlite3 php8.2-redis php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath php8.2-dom \
    php8.2-fileinfo php8.2-json php8.2-tokenizer

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Supervisor
sudo apt install -y supervisor
```

### 2. Create Application User

```bash
# Create dedicated user for the application
sudo adduser --system --group --home /var/www amksocial
sudo mkdir -p /var/www/html
sudo chown amksocial:amksocial /var/www/html
```

### 3. Database Setup

#### PostgreSQL Setup

```bash
# Switch to postgres user
sudo su - postgres

# Create database and user
createdb amksocial
createuser --pwprompt amksocial

# Grant privileges
psql -c "GRANT ALL PRIVILEGES ON DATABASE amksocial TO amksocial;"
psql -c "ALTER USER amksocial CREATEDB;"

# Exit postgres user
exit
```

#### MySQL Setup (Alternative)

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE amksocial CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'amksocial'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON amksocial.* TO 'amksocial'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Redis Configuration

```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf

# Recommended settings:
# maxmemory 256mb
# maxmemory-policy allkeys-lru
# save 900 1
# save 300 10
# save 60 10000

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

## Environment Setup

### 1. Clone and Deploy Application

```bash
# Switch to application user
sudo su - amksocial

# Clone repository
cd /var/www
git clone https://github.com/your-username/amksocial.git html
cd html

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies
cd social_react
npm ci --production
npm run build
cd ..

# Set up environment file
cp .env.example .env
```

### 2. Configure Environment Variables

Edit `/var/www/html/.env`:

```env
# Application
APP_NAME="AMKSocial"
APP_ENV=production
APP_KEY=base64:your_generated_app_key
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=amksocial
DB_USERNAME=amksocial
DB_PASSWORD=your_secure_password

# Cache & Sessions
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Broadcasting
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

# Mail (Configure according to your provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="AMKSocial"

# File Storage
FILESYSTEM_DISK=public
AWS_BUCKET=your_s3_bucket  # Optional: for S3 storage
AWS_REGION=us-east-1
```

### 3. Generate Application Key and Optimize

```bash
# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force

# Create storage link
php artisan storage:link

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Set proper permissions
sudo chown -R amksocial:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 775 /var/www/html/storage
sudo chmod -R 775 /var/www/html/bootstrap/cache
```

## Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/amksocial`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/html/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:MozTLS:10m;
    ssl_session_tickets off;

    # Modern SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # HSTS (optional)
    add_header Strict-Transport-Security "max-age=63072000" always;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;

    charset utf-8;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API endpoints with rate limiting
    location /api/ {
        limit_req zone=api burst=10 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Login endpoints with stricter rate limiting
    location ~ ^/api/(login|register) {
        limit_req zone=login burst=3 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for file uploads
        fastcgi_read_timeout 300;
        client_max_body_size 50M;
    }

    # Storage files
    location /storage {
        alias /var/www/html/storage/app/public;
        expires 1M;
        add_header Cache-Control "public, immutable";
        add_header X-Content-Type-Options nosniff;
    }

    # Static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header X-Content-Type-Options nosniff;
    }

    # WebSocket proxy for Reverb
    location /app/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ ^/(\.env|\.git|composer\.(json|lock)|package\.json|README\.md)$ {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
}
```

### Enable Nginx Site

```bash
# Enable the site
sudo ln -s /etc/nginx/sites-available/amksocial /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

### PHP-FPM Configuration

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
; Performance settings
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
post_max_size = 50M
upload_max_filesize = 50M
max_file_uploads = 20

; Security settings
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Session settings
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On

; OPcache settings
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
; Process management
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 1000

; Security
security.limit_extensions = .php
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
```

## SSL Certificate Setup

### Using Let's Encrypt (Recommended)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test automatic renewal
sudo certbot renew --dry-run

# Set up automatic renewal cron job
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

### Manual SSL Certificate

If using a purchased SSL certificate:

```bash
# Copy certificate files
sudo mkdir -p /etc/ssl/certs/amksocial
sudo cp your_domain.crt /etc/ssl/certs/amksocial/
sudo cp your_domain.key /etc/ssl/private/amksocial/
sudo cp ca_bundle.crt /etc/ssl/certs/amksocial/

# Set proper permissions
sudo chmod 644 /etc/ssl/certs/amksocial/*
sudo chmod 600 /etc/ssl/private/amksocial/*

# Update Nginx configuration with correct paths
```

## WebSocket Configuration

### Reverb Service Setup

Create `/etc/systemd/system/amksocial-reverb.service`:

```ini
[Unit]
Description=AMKSocial Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=amksocial
Group=amksocial
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=amksocial-reverb

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable amksocial-reverb
sudo systemctl start amksocial-reverb
```

### Firewall Configuration

```bash
# Install UFW (if not already installed)
sudo apt install -y ufw

# Configure firewall rules
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw allow 8080/tcp  # For WebSocket

# Enable firewall
sudo ufw enable
```

## File Storage Setup

### Local Storage (Default)

```bash
# Ensure proper permissions for storage
sudo chown -R amksocial:www-data /var/www/html/storage
sudo chmod -R 775 /var/www/html/storage

# Create required directories
mkdir -p /var/www/html/storage/app/public/posts
mkdir -p /var/www/html/storage/app/public/avatars
mkdir -p /var/www/html/storage/app/public/covers

# Symbolic link for public access
php artisan storage:link
```

### Amazon S3 Setup (Optional)

If using S3 for file storage, install the S3 package:

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

Update `.env`:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Process Management

### Supervisor Configuration

Create `/etc/supervisor/conf.d/amksocial.conf`:

```ini
[group:amksocial]
programs=amksocial-queue,amksocial-reverb

[program:amksocial-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/html
user=amksocial
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/amksocial-queue.log
stopwaitsecs=3600
startsecs=0
autorestart=true

[program:amksocial-reverb]
process_name=%(program_name)s
command=php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/var/www/html
user=amksocial
redirect_stderr=true
stdout_logfile=/var/log/amksocial-reverb.log
startsecs=0
autorestart=true
```

Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start amksocial:*

# Check status
sudo supervisorctl status
```

### Systemd Services (Alternative)

Create `/etc/systemd/system/amksocial-queue.service`:

```ini
[Unit]
Description=AMKSocial Queue Worker
After=network.target

[Service]
Type=simple
User=amksocial
Group=amksocial
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Enable services:

```bash
sudo systemctl daemon-reload
sudo systemctl enable amksocial-queue
sudo systemctl start amksocial-queue
```

## Monitoring and Maintenance

### Log Management

Configure log rotation in `/etc/logrotate.d/amksocial`:

```
/var/www/html/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 amksocial amksocial
    postrotate
        /bin/systemctl reload php8.2-fpm
    endscript
}

/var/log/amksocial-*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 amksocial amksocial
}
```

### Health Monitoring

Create a health check script `/var/www/html/health-check.sh`:

```bash
#!/bin/bash

# Check application status
curl -f http://localhost/api/health > /dev/null 2>&1
APP_STATUS=$?

# Check database connectivity
sudo -u amksocial php /var/www/html/artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1
DB_STATUS=$?

# Check queue worker
pgrep -f "queue:work" > /dev/null 2>&1
QUEUE_STATUS=$?

# Check WebSocket server
curl -f http://localhost:8080 > /dev/null 2>&1
WS_STATUS=$?

# Send alerts if any service is down
if [ $APP_STATUS -ne 0 ] || [ $DB_STATUS -ne 0 ] || [ $QUEUE_STATUS -ne 0 ] || [ $WS_STATUS -ne 0 ]; then
    echo "Health check failed at $(date)" >> /var/log/amksocial-health.log
    # Add your alerting mechanism here (email, Slack, etc.)
fi
```

Add to crontab:

```bash
sudo crontab -e
*/5 * * * * /var/www/html/health-check.sh
```

### Performance Monitoring

Install monitoring tools:

```bash
# Install htop for system monitoring
sudo apt install -y htop

# Install iotop for disk I/O monitoring
sudo apt install -y iotop

# Install netstat for network monitoring
sudo apt install -y net-tools
```

Set up application performance monitoring in Laravel:

```php
// config/app.php
'providers' => [
    // Other providers...
    Spatie\LaravelRay\RayServiceProvider::class, // For debugging
];
```

## Performance Optimization

### Database Optimization

#### PostgreSQL Configuration

Edit `/etc/postgresql/13/main/postgresql.conf`:

```ini
# Memory settings
shared_buffers = 1GB                    # 25% of RAM
effective_cache_size = 3GB              # 75% of RAM
work_mem = 16MB
maintenance_work_mem = 256MB

# Connection settings
max_connections = 200

# Write-ahead logging
wal_buffers = 16MB
checkpoint_completion_target = 0.9

# Query planner
random_page_cost = 1.1                  # For SSD storage
effective_io_concurrency = 200          # For SSD storage

# Logging
log_statement = 'mod'
log_min_duration_statement = 1000       # Log slow queries
```

Restart PostgreSQL:

```bash
sudo systemctl restart postgresql
```

#### Database Indexing

```sql
-- Add indexes for performance
CREATE INDEX CONCURRENTLY idx_posts_user_id_created_at ON posts(user_id, created_at DESC);
CREATE INDEX CONCURRENTLY idx_messages_conversation_id_created_at ON messages(conversation_id, created_at DESC);
CREATE INDEX CONCURRENTLY idx_friendships_user_status ON friendships(user_id, status);
CREATE INDEX CONCURRENTLY idx_friendships_friend_status ON friendships(friend_id, status);
CREATE INDEX CONCURRENTLY idx_likes_user_post ON likes(user_id, post_id);
CREATE INDEX CONCURRENTLY idx_comments_post_parent ON comments(post_id, parent_id);
```

### Redis Optimization

Edit `/etc/redis/redis.conf`:

```ini
# Memory management
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence (adjust based on requirements)
save 900 1
save 300 10
save 60 10000

# Network settings
tcp-keepalive 300
timeout 0

# Security
requirepass your_redis_password
```

### Application Caching

Set up advanced caching in Laravel:

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

Add caching middleware to routes:

```php
// routes/api.php
Route::middleware(['cache.headers:public;max_age=300;etag'])->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

### Frontend Optimization

Build optimized React application:

```bash
cd social_react

# Install dependencies
npm ci --production

# Build with optimizations
npm run build

# Analyze bundle size (optional)
npm install -g webpack-bundle-analyzer
npx webpack-bundle-analyzer build/static/js/*.js
```

### CDN Setup (Optional)

If using a CDN like CloudFlare:

1. Point your domain's DNS to CloudFlare
2. Configure SSL/TLS to "Full (strict)"
3. Enable compression and caching rules
4. Set up page rules for static assets

## Backup Strategy

### Database Backup

Create backup script `/usr/local/bin/backup-amksocial.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/amksocial"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="amksocial"
DB_USER="amksocial"

# Create backup directory
mkdir -p $BACKUP_DIR

# PostgreSQL backup
export PGPASSWORD="your_db_password"
pg_dump -h localhost -U $DB_USER -d $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/db_backup_$DATE.sql

# Application files backup
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz -C /var/www/html storage/app/public

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

# Upload to remote storage (optional)
# aws s3 cp $BACKUP_DIR/db_backup_$DATE.sql.gz s3://your-backup-bucket/
```

Make executable and add to crontab:

```bash
sudo chmod +x /usr/local/bin/backup-amksocial.sh

# Add to crontab for daily backups at 2 AM
sudo crontab -e
0 2 * * * /usr/local/bin/backup-amksocial.sh
```

### Application Backup

```bash
# Full application backup
tar -czf amksocial_full_backup_$(date +%Y%m%d).tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    /var/www/html
```

## Troubleshooting

### Common Issues

#### 1. Permission Issues

```bash
# Fix file permissions
sudo chown -R amksocial:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 775 /var/www/html/storage
sudo chmod -R 775 /var/www/html/bootstrap/cache
```

#### 2. Queue Worker Not Processing Jobs

```bash
# Check queue worker status
sudo supervisorctl status amksocial-queue:*

# Restart queue workers
sudo supervisorctl restart amksocial-queue:*

# Check logs
tail -f /var/log/amksocial-queue.log
```

#### 3. WebSocket Connection Issues

```bash
# Check Reverb service
sudo systemctl status amksocial-reverb

# Check if port is listening
sudo netstat -tlnp | grep :8080

# Test WebSocket connection
curl -i -N -H "Connection: Upgrade" \
     -H "Upgrade: websocket" \
     -H "Origin: https://yourdomain.com" \
     -H "Sec-WebSocket-Key: SGVsbG8sIHdvcmxkIQ==" \
     -H "Sec-WebSocket-Version: 13" \
     https://yourdomain.com/app/your_app_key
```

#### 4. File Upload Issues

```bash
# Check PHP upload settings
php -i | grep upload

# Check disk space
df -h

# Check storage permissions
ls -la /var/www/html/storage/app/public
```

#### 5. High Memory Usage

```bash
# Check memory usage
free -h
top

# Optimize PHP-FPM
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
# Adjust pm.max_children based on available memory

# Clear application caches
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Log Analysis

#### Application Logs

```bash
# Laravel logs
tail -f /var/www/html/storage/logs/laravel.log

# Nginx access logs
tail -f /var/log/nginx/access.log

# Nginx error logs
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

#### System Logs

```bash
# System logs
journalctl -u amksocial-reverb -f
journalctl -u amksocial-queue -f

# PostgreSQL logs
tail -f /var/log/postgresql/postgresql-13-main.log

# Redis logs
tail -f /var/log/redis/redis-server.log
```

### Performance Troubleshooting

#### Database Performance

```sql
-- Check slow queries
SELECT query, mean_time, calls, total_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 10;

-- Check database connections
SELECT * FROM pg_stat_activity;

-- Check table sizes
SELECT schemaname, tablename, 
       pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

#### Application Performance

```bash
# Check queue job status
php artisan queue:monitor

# Check application performance
php artisan telescope:install  # Development only

# Profile database queries
php artisan tinker
DB::enableQueryLog();
// Run your operations
DB::getQueryLog();
```

This deployment guide provides comprehensive instructions for setting up AMKSocial in a production environment. Always test deployments in a staging environment first and ensure you have proper backups before making changes to production systems.

# Buffalo Marathon 2025 - Production Deployment Guide

## Overview
This document provides comprehensive instructions for deploying the Buffalo Marathon registration system to a production environment with ZANACO payment integration and buffalo-marathon.com email server.

## System Requirements

### Server Specifications
- **PHP**: 7.4+ (8.1+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.2+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL Certificate**: Required for production
- **Storage**: Minimum 2GB available space
- **Memory**: 512MB+ PHP memory limit

### PHP Extensions Required
```bash
php-mysqli
php-pdo
php-pdo-mysql
php-curl
php-gd
php-json
php-mbstring
php-openssl
php-session
php-zip
```

## Pre-Deployment Checklist

### 1. Domain and Hosting Setup
- [x] Domain: buffalo-marathon.com configured
- [x] Email server: mail.buffalo-marathon.com setup
- [x] SSL certificate installed
- [x] DNS records configured (A, CNAME, MX)

### 2. Database Configuration
- [x] MySQL database created
- [x] Database user with appropriate privileges
- [x] Schema v2.0 imported
- [x] Test data cleared for production

### 3. Payment Integration
- [x] ZANACO bank details configured
- [x] Mobile money shortcodes verified
- [x] Payment notification emails tested

## Deployment Steps

### Step 1: File Upload and Permissions

```bash
# Upload files to server
scp -r buffalo-marathon/* user@server:/var/www/buffalo-marathon/

# Set proper permissions
chmod 755 /var/www/buffalo-marathon
chmod 644 /var/www/buffalo-marathon/*.php
chmod 755 /var/www/buffalo-marathon/assets/
chmod 755 /var/www/buffalo-marathon/uploads/
chmod 777 /var/www/buffalo-marathon/uploads/  # For file uploads
```

### Step 2: Database Setup

```sql
-- Create production database
CREATE DATABASE buffalo_marathon_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create database user
CREATE USER 'buffalo_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON buffalo_marathon_prod.* TO 'buffalo_user'@'localhost';
FLUSH PRIVILEGES;

-- Import schema
mysql -u buffalo_user -p buffalo_marathon_prod < database/schema.sql
```

### Step 3: Configuration Updates

#### config/config.php Production Settings
```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'buffalo_marathon_prod');
define('DB_USER', 'buffalo_user');
define('DB_PASS', 'your_secure_password');

// Site Configuration
define('SITE_URL', 'https://buffalo-marathon.com');
define('SITE_EMAIL', 'info@buffalo-marathon.com');

// Email Configuration (Production)
define('SMTP_HOST', 'mail.buffalo-marathon.com');
define('SMTP_USERNAME', 'system@buffalo-marathon.com');
define('SMTP_PASSWORD', 'your_smtp_password');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');

// Security
define('CSRF_TOKEN_EXPIRY', 3600);
define('SESSION_TIMEOUT', 1800);
define('RATE_LIMIT_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 300);

// Production Mode
define('DEVELOPMENT_MODE', false);
define('DEBUG_MODE', false);
```

### Step 4: Web Server Configuration

#### Apache Virtual Host (.htaccess)
```apache
<VirtualHost *:443>
    ServerName buffalo-marathon.com
    ServerAlias www.buffalo-marathon.com
    DocumentRoot /var/www/buffalo-marathon
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/buffalo-marathon>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName buffalo-marathon.com
    ServerAlias www.buffalo-marathon.com
    Redirect permanent / https://buffalo-marathon.com/
</VirtualHost>
```

#### Root .htaccess File
```apache
# Buffalo Marathon Security and Routing
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security: Block access to sensitive files
<FilesMatch "\.(env|log|sql|md|json)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block access to config and includes directories
RedirectMatch 404 /config/
RedirectMatch 404 /includes/
RedirectMatch 404 /database/

# PHP Security
php_flag display_errors Off
php_flag log_errors On
php_value error_log /var/log/php/buffalo-errors.log

# Performance
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### Step 5: Email Server Configuration

#### Email DNS Records
```dns
; MX Record
buffalo-marathon.com.    IN    MX    10    mail.buffalo-marathon.com.

; A Record for mail server
mail.buffalo-marathon.com.    IN    A    YOUR_SERVER_IP

; SPF Record
buffalo-marathon.com.    IN    TXT    "v=spf1 mx a:mail.buffalo-marathon.com -all"

; DMARC Record
_dmarc.buffalo-marathon.com.    IN    TXT    "v=DMARC1; p=reject; rua=mailto:dmarc@buffalo-marathon.com"
```

### Step 6: Payment Integration Verification

#### ZANACO Bank Details Verification
```php
// Verify bank configuration
Bank Name: ZANACO
Account Name: Buffalo Park Recreation Center
Account Number: 0307107300745
Branch: Government Complex Branch
SWIFT Code: ZANOZM22
Currency: ZMW (Zambian Kwacha)
```

#### Mobile Money Shortcodes
```php
MTN Mobile Money: 4545
Airtel Money: 6060
Zamtel Kwacha: 3535
```

### Step 7: Security Hardening

#### File Permissions (Production)
```bash
# Set secure permissions
find /var/www/buffalo-marathon -type d -exec chmod 755 {} \;
find /var/www/buffalo-marathon -type f -exec chmod 644 {} \;
chmod 600 /var/www/buffalo-marathon/config/*.php
chmod 700 /var/www/buffalo-marathon/uploads/
chmod 755 /var/www/buffalo-marathon/assets/

# Create log directory
mkdir -p /var/log/buffalo-marathon
chown www-data:www-data /var/log/buffalo-marathon
chmod 755 /var/log/buffalo-marathon
```

#### PHP Security (php.ini)
```ini
; Production Security Settings
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/buffalo-errors.log
allow_url_fopen = Off
allow_url_include = Off
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
upload_max_filesize = 5M
post_max_size = 10M
memory_limit = 512M
max_execution_time = 300
```

## Post-Deployment Testing

### 1. System Health Check
```bash
# Test database connection
php -r "
include 'config/database.php';
try {
    \$pdo = getDB();
    echo 'Database connection: OK\n';
} catch (Exception \$e) {
    echo 'Database error: ' . \$e->getMessage() . '\n';
}
"

# Test email configuration
php -r "
include 'includes/functions.php';
\$result = sendEmail('test@buffalo-marathon.com', 'Test Email', 'System test email');
echo 'Email test: ' . (\$result ? 'OK' : 'FAILED') . '\n';
"
```

### 2. Registration Flow Test
1. **Homepage Access**: Visit https://buffalo-marathon.com
2. **User Registration**: Create test account
3. **Marathon Registration**: Register for marathon category
4. **Payment Process**: Test each payment method
5. **Email Notifications**: Verify email delivery
6. **Admin Dashboard**: Test administrative functions

### 3. Payment Method Testing

#### Bank Transfer Test
1. Generate test registration
2. Display ZANACO bank details
3. Verify account information accuracy
4. Test payment confirmation flow

#### Mobile Money Test
1. Test MTN shortcode: 4545
2. Test Airtel shortcode: 6060
3. Test Zamtel shortcode: 3535
4. Verify reference number format

#### Cash Payment Test
1. Display office location details
2. Verify contact information
3. Test office hours display

## Monitoring and Maintenance

### 1. Log Files to Monitor
```bash
# System logs
/var/log/apache2/error.log
/var/log/apache2/access.log
/var/log/php/buffalo-errors.log
/var/log/buffalo-marathon/application.log

# Database logs
/var/log/mysql/error.log
```

### 2. Performance Monitoring
- Monitor registration volumes
- Track payment processing times
- Monitor email delivery rates
- Check database performance

### 3. Security Monitoring
- Failed login attempts
- Suspicious payment activities
- File upload monitoring
- Session security

### 4. Regular Maintenance Tasks
- **Daily**: Check error logs, monitor registration numbers
- **Weekly**: Database backup, security updates
- **Monthly**: Performance review, capacity planning

## Backup and Recovery

### 1. Database Backup
```bash
# Daily automated backup
mysqldump -u buffalo_user -p buffalo_marathon_prod > /backups/buffalo_$(date +%Y%m%d).sql

# Weekly full backup with compression
mysqldump -u buffalo_user -p buffalo_marathon_prod | gzip > /backups/buffalo_weekly_$(date +%Y%m%d).sql.gz
```

### 2. File System Backup
```bash
# Backup uploads and configuration
tar -czf /backups/buffalo_files_$(date +%Y%m%d).tar.gz /var/www/buffalo-marathon/uploads/ /var/www/buffalo-marathon/config/
```

### 3. Recovery Procedures
```bash
# Database recovery
mysql -u buffalo_user -p buffalo_marathon_prod < /backups/buffalo_YYYYMMDD.sql

# File recovery
tar -xzf /backups/buffalo_files_YYYYMMDD.tar.gz -C /var/www/
```

## Emergency Contacts

### Technical Support
- **System Administrator**: admin@buffalo-marathon.com
- **Database Administrator**: dba@buffalo-marathon.com
- **Emergency Phone**: +260-XXX-XXXXXX

### Banking Contacts
- **ZANACO Contact**: Government Complex Branch
- **Mobile Money Support**: Network provider technical teams

## Success Metrics

### Key Performance Indicators
- **Registration Completion Rate**: >95%
- **Payment Success Rate**: >98%
- **Email Delivery Rate**: >99%
- **Page Load Time**: <3 seconds
- **System Uptime**: >99.9%

### Payment Processing Metrics
- **Bank Transfer Processing**: 24-48 hours
- **Mobile Money Processing**: Instant verification
- **Cash Payment Recording**: Same day processing

## Version History

- **v1.0**: Initial deployment (August 2025)
- **v2.0**: ZANACO integration, email server configuration
- **v2.1**: Mobile money integration, enhanced security

---

**Deployment Date**: August 13, 2025  
**System Status**: Production Ready  
**Last Updated**: August 13, 2025  
**Next Review**: September 1, 2025

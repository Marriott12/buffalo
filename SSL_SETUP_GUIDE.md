# Buffalo Marathon 2025 - SSL/HTTPS Setup Guide

## 📜 SSL Certificate Setup

### Option 1: Let's Encrypt (Free)
```bash
# Install Certbot
sudo apt update
sudo apt install certbot python3-certbot-apache

# Get certificate for your domain
sudo certbot --apache -d buffalo-marathon.com -d www.buffalo-marathon.com

# Auto-renewal setup
sudo crontab -e
# Add this line:
0 12 * * * /usr/bin/certbot renew --quiet
```

### Option 2: Commercial SSL Certificate
1. Purchase SSL certificate from provider
2. Generate CSR (Certificate Signing Request)
3. Install certificate files on server
4. Configure Apache/Nginx

## 🔧 Apache Configuration (with SSL)

Create/Update `/etc/apache2/sites-available/buffalo-marathon-ssl.conf`:

```apache
<VirtualHost *:443>
    ServerName buffalo-marathon.com
    ServerAlias www.buffalo-marathon.com
    DocumentRoot /var/www/buffalo-marathon
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/chain.crt
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    # Directory Configuration
    <Directory /var/www/buffalo-marathon>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/buffalo-marathon-error.log
    CustomLog ${APACHE_LOG_DIR}/buffalo-marathon-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName buffalo-marathon.com
    ServerAlias www.buffalo-marathon.com
    Redirect permanent / https://buffalo-marathon.com/
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite buffalo-marathon-ssl
sudo a2enmod ssl
sudo a2enmod headers
sudo systemctl reload apache2
```

## 🔧 Nginx Configuration (with SSL)

Create/Update `/etc/nginx/sites-available/buffalo-marathon`:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name buffalo-marathon.com www.buffalo-marathon.com;
    return 301 https://buffalo-marathon.com$request_uri;
}

# HTTPS Configuration
server {
    listen 443 ssl http2;
    server_name buffalo-marathon.com www.buffalo-marathon.com;
    root /var/www/buffalo-marathon;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_trusted_certificate /path/to/chain.crt;
    
    # SSL Security
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options DENY always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # PHP Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Security - Block sensitive files
    location ~* \.(env|sql|log|bak|backup|config|ini)$ {
        deny all;
    }
    
    # Block access to sensitive directories
    location ~* /(logs|cache|backups|config)/ {
        deny all;
    }
    
    # Static files caching
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/buffalo-marathon /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 🔧 Update PHP Configuration for HTTPS

After SSL is configured, update `.htaccess`:

```apache
# Force HTTPS (uncomment after SSL setup)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## ✅ SSL Verification Checklist

1. **Certificate Installation**
   - [ ] SSL certificate installed correctly
   - [ ] Certificate chain complete
   - [ ] Private key matches certificate

2. **HTTP to HTTPS Redirect**
   - [ ] All HTTP traffic redirects to HTTPS
   - [ ] Redirect is permanent (301)
   - [ ] No redirect loops

3. **Security Headers**
   - [ ] HSTS header present
   - [ ] X-Content-Type-Options set
   - [ ] X-Frame-Options configured
   - [ ] X-XSS-Protection enabled

4. **SSL Configuration**
   - [ ] Strong SSL/TLS protocols only
   - [ ] Secure cipher suites
   - [ ] Perfect Forward Secrecy enabled

5. **Testing**
   - [ ] SSL Labs test: A+ rating
   - [ ] All pages load over HTTPS
   - [ ] Mixed content warnings resolved
   - [ ] Forms submit securely

## 🧪 SSL Testing Tools

1. **SSL Labs**: https://www.ssllabs.com/ssltest/
2. **Certificate Decoder**: https://www.sslshopper.com/certificate-decoder.html
3. **Mixed Content Checker**: https://mixed-content-checker.netlify.app/

## 📞 Support

If you need help with SSL setup:
- **Phone**: +260 972 545 658 / +260 770 809 062 / +260 771 470 868
- **Email**: info@buffalo-marathon.com

## ⚠️ Important Notes

1. **Test thoroughly** before going live
2. **Backup** before making SSL changes
3. **Update** all internal links to HTTPS
4. **Check** third-party integrations for HTTPS compatibility
5. **Monitor** error logs after SSL deployment

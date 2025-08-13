# Buffalo Marathon 2025 Registration System

## 🏃‍♂️ Production-Ready Marathon Registration Platform

A comprehensive PHP-based registration system for the Buffalo Marathon 2025 event, featuring integrated ZANACO payment processing, mobile money support, and professional email communications.

## ✨ Key Features

### Registration Management
- **Multi-Category Registration**: 5K Fun Run, 10K Race, Half Marathon, Full Marathon
- **User Authentication**: Secure login/registration system with CSRF protection
- **Profile Management**: Complete participant profile system
- **Registration Tracking**: Unique registration numbers with QR codes

### Payment Integration
- **ZANACO Bank Transfer**: Direct integration with Government Complex Branch
- **Mobile Money Support**: MTN, Airtel, and Zamtel payment options
- **Cash Payments**: On-site payment processing
- **Payment Verification**: Automated status tracking and confirmation

### Communication System
- **Professional Email Server**: buffalo-marathon.com email integration
- **Automated Notifications**: Registration confirmations and payment updates
- **SMS Integration**: Mobile notifications for payment confirmations
- **Admin Notifications**: Real-time registration and payment alerts

### Administrative Dashboard
- **Registration Analytics**: Real-time participant statistics
- **Payment Management**: Payment verification and tracking
- **Report Generation**: Comprehensive registration and financial reports
- **User Management**: Administrative user controls

## 🛠 Technical Stack

### Backend
- **PHP 8.1+**: Modern PHP with strong typing and security features
- **MySQL 8.0**: Robust database with comprehensive schema v2.0
- **Session Management**: Secure session handling with timeout protection
- **Email Integration**: PHPMailer with SMTP configuration

### Frontend
- **Bootstrap 5**: Responsive design framework
- **Army Green Theme**: Professional military-inspired color scheme (#4B5320)
- **Mobile-First Design**: Optimized for all device sizes
- **Progressive Enhancement**: Graceful degradation for older browsers

### Security Features
- **CSRF Protection**: Cross-site request forgery prevention
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output encoding
- **Session Security**: Secure session configuration
- **Rate Limiting**: Brute force attack prevention

## 🚀 Quick Start

### Production Deployment
1. **Server Requirements**: PHP 8.1+, MySQL 8.0+, SSL certificate
2. **Configuration**: Update `config/config.php` with production settings
3. **Database Setup**: Import `database/schema.sql` version 2.0
4. **Email Configuration**: Configure buffalo-marathon.com SMTP settings
5. **Payment Setup**: Verify ZANACO bank details and mobile money shortcodes

### Development Setup
```bash
# Clone repository
git clone https://github.com/Marriott12/buffalo.git
cd buffalo

# Configure database
mysql -u root -p < database/schema.sql

# Update configuration
cp config/config.sample.php config/config.php
# Edit config.php with your settings

# Start development server
php -S localhost:8000
```

## 💳 Payment Configuration

### ZANACO Bank Details
```
Bank Name: ZANACO
Account Name: Buffalo Park Recreation Center
Account Number: 0307107300745
Branch: Government Complex Branch
SWIFT Code: ZANOZM22
Currency: ZMW (Zambian Kwacha)
```

### Mobile Money Shortcodes
- **MTN Mobile Money**: 4545
- **Airtel Money**: 6060
- **Zamtel Kwacha**: 3535

## 📧 Email Configuration

### Primary Email Server
- **Host**: mail.buffalo-marathon.com
- **Port**: 587 (TLS) / 465 (SSL)
- **From Address**: info@buffalo-marathon.com
- **System Address**: system@buffalo-marathon.com

### Email Templates
- Registration confirmation emails
- Payment notification emails
- Administrative alert emails
- Password reset emails

## 📊 System Architecture

### File Structure
```
buffalo-marathon/
├── config/              # Configuration files
│   ├── config.php      # Main configuration
│   └── database.php    # Database connection
├── includes/           # Core PHP includes
│   ├── functions.php   # Utility functions
│   ├── header.php      # Site header
│   ├── footer.php      # Site footer
│   ├── security.php    # Security functions
│   └── payment.php     # Payment processing
├── assets/             # Static assets
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── admin/             # Administrative interface
├── database/          # Database schema and migrations
└── uploads/           # File upload directory
```

### Database Schema v2.0
- **users**: User accounts and profiles
- **categories**: Marathon categories and pricing
- **registrations**: Registration records
- **payments**: Payment transactions
- **settings**: System configuration
- **activity_logs**: Audit trail

## 🔧 Configuration Options

### Site Settings
```php
// Site Configuration
define('SITE_NAME', 'Buffalo Marathon 2025');
define('SITE_URL', 'https://buffalo-marathon.com');
define('SITE_EMAIL', 'info@buffalo-marathon.com');

// Event Details
define('EVENT_DATE', '2025-09-15');
define('EVENT_VENUE', 'Buffalo Park Recreation Center');
define('REGISTRATION_DEADLINE', '2025-09-01');
```

### Security Settings
```php
// Security Configuration
define('CSRF_TOKEN_EXPIRY', 3600);
define('SESSION_TIMEOUT', 1800);
define('RATE_LIMIT_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 300);
```

## 📱 Mobile Support

### Responsive Design
- Mobile-first approach with Bootstrap 5
- Touch-friendly interface elements
- Optimized forms for mobile input
- Fast loading on slow connections

### Mobile Money Integration
- Native mobile money payment flows
- SMS confirmation support
- USSD integration capabilities
- Mobile-optimized payment instructions

## 🛡 Security Features

### Authentication Security
- Password hashing with PHP's password_hash()
- Session regeneration for security
- Login attempt rate limiting
- Secure password reset functionality

### Data Protection
- HTTPS enforcement in production
- Secure cookie configuration
- Input validation and sanitization
- SQL injection prevention

### Payment Security
- PCI-DSS compliance considerations
- Secure payment form handling
- Transaction logging and audit trails
- Fraud detection capabilities

## 📈 Performance Optimization

### Caching Strategy
- Browser caching for static assets
- Database query optimization
- Session data optimization
- Email template caching

### Database Performance
- Indexed columns for fast queries
- Optimized table structures
- Connection pooling support
- Query performance monitoring

## 🔍 Monitoring and Analytics

### System Monitoring
- Registration volume tracking
- Payment success rate monitoring
- Email delivery rate tracking
- Error rate monitoring

### Business Analytics
- Category popularity analysis
- Payment method preferences
- Geographic distribution of participants
- Registration timeline analysis

## 🚨 Troubleshooting

### Common Issues
1. **Email Delivery Problems**: Check SMTP configuration and DNS records
2. **Payment Integration Issues**: Verify bank details and mobile money codes
3. **Database Connection Errors**: Check credentials and server status
4. **Session Timeout Issues**: Adjust session configuration

### Debug Mode
```php
// Enable debug mode for development
define('DEBUG_MODE', true);
define('DEVELOPMENT_MODE', true);
```

## 📞 Support

### Technical Support
- **Email**: support@buffalo-marathon.com
- **Phone**: +260-XXX-XXXXXX
- **Emergency**: admin@buffalo-marathon.com

### Banking Support
- **ZANACO**: Government Complex Branch
- **Mobile Money**: Network provider support

## 🤝 Contributing

### Development Guidelines
1. Follow PSR-12 coding standards
2. Write comprehensive comments
3. Include security considerations
4. Test payment flows thoroughly
5. Maintain backward compatibility

### Code Review Process
1. Create feature branch
2. Implement changes with tests
3. Submit pull request
4. Code review and approval
5. Deploy to staging environment
6. Production deployment

## 📄 License

Copyright © 2025 Buffalo Marathon Organization. All rights reserved.

This software is proprietary and confidential. Unauthorized copying, distribution, or modification is strictly prohibited.

## 🏆 Success Metrics

### Performance Targets
- **Registration Completion Rate**: >95%
- **Payment Success Rate**: >98%
- **Email Delivery Rate**: >99%
- **Page Load Time**: <3 seconds
- **System Uptime**: >99.9%

### Business Goals
- Process 1000+ registrations
- Support multiple payment methods
- Maintain excellent user experience
- Ensure data security and compliance

---

**Version**: 2.0 Production Ready  
**Last Updated**: August 13, 2025  
**Deployment Status**: Production Ready  
**Next Review**: September 1, 2025

For detailed deployment instructions, see [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

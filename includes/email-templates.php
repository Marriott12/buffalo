<?php
/**
 * Buffalo Marathon 2025 - Email Templates System
 * Advanced email templating with database storage
 * Created: 2025-01-09
 */

// Security check
if (!defined('BUFFALO_CONFIG_LOADED')) {
    define('BUFFALO_SECURE_ACCESS', true);
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
}

class EmailTemplateSystem {
    private static ?EmailTemplateSystem $instance = null;
    private PDO $db;
    private array $cache = [];
    
    private function __construct() {
        $this->db = getDB();
    }
    
    public static function getInstance(): EmailTemplateSystem {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get email template
     */
    public function getTemplate(string $templateName): ?array {
        if (isset($this->cache[$templateName])) {
            return $this->cache[$templateName];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM email_templates 
                WHERE template_name = ? AND is_active = 1
            ");
            $stmt->execute([$templateName]);
            
            $template = $stmt->fetch();
            if ($template) {
                $this->cache[$templateName] = $template;
                return $template;
            }
            
            return null;
            
        } catch (Exception $e) {
            log_error("Error getting email template: " . $e->getMessage(), 'email');
            return null;
        }
    }
    
    /**
     * Render email template with variables
     */
    public function render(string $templateName, array $variables = []): ?array {
        $template = $this->getTemplate($templateName);
        if (!$template) {
            return null;
        }
        
        $subject = $this->replaceVariables($template['subject'], $variables);
        $bodyHtml = $this->replaceVariables($template['body_html'], $variables);
        $bodyText = $template['body_text'] ? $this->replaceVariables($template['body_text'], $variables) : strip_tags($bodyHtml);
        
        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'template_name' => $templateName
        ];
    }
    
    /**
     * Send email using template
     */
    public function sendEmail(string $to, string $templateName, array $variables = [], string $toName = ''): bool {
        $rendered = $this->render($templateName, $variables);
        if (!$rendered) {
            log_error("Failed to render email template: {$templateName}", 'email');
            return false;
        }
        
        return $this->queueEmail($to, $rendered['subject'], $rendered['body_html'], $toName);
    }
    
    /**
     * Replace variables in text
     */
    private function replaceVariables(string $text, array $variables): string {
        foreach ($variables as $key => $value) {
            $text = str_replace("{{$key}}", htmlspecialchars((string)$value), $text);
        }
        
        // Replace common system variables
        $systemVars = [
            'site_name' => SITE_NAME,
            'site_url' => SITE_URL,
            'site_email' => SITE_EMAIL,
            'marathon_date' => date('F j, Y', strtotime(MARATHON_DATE)),
            'marathon_time' => date('g:i A', strtotime(MARATHON_TIME)),
            'venue' => EVENT_VENUE,
            'address' => EVENT_ADDRESS,
            'city' => EVENT_CITY,
            'current_year' => date('Y'),
            'registration_deadline' => date('F j, Y', strtotime(REGISTRATION_DEADLINE))
        ];
        
        foreach ($systemVars as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        
        return $text;
    }
    
    /**
     * Queue email for sending
     */
    private function queueEmail(string $to, string $subject, string $body, string $toName = ''): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_queue (to_email, to_name, subject, body, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            
            $success = $stmt->execute([$to, $toName, $subject, $body]);
            
            if ($success) {
                log_email('queued', $to, $subject);
            }
            
            return $success;
            
        } catch (Exception $e) {
            log_error("Error queuing email: " . $e->getMessage(), 'email');
            return false;
        }
    }
    
    /**
     * Create or update template
     */
    public function createTemplate(string $name, string $subject, string $bodyHtml, string $bodyText = '', array $variables = [], int $createdBy = null): bool {
        try {
            $createdBy = $createdBy ?? ($_SESSION['user_id'] ?? 1);
            
            $stmt = $this->db->prepare("
                INSERT INTO email_templates (template_name, subject, body_html, body_text, variables, created_by) 
                VALUES (?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                subject = VALUES(subject), 
                body_html = VALUES(body_html), 
                body_text = VALUES(body_text), 
                variables = VALUES(variables),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $success = $stmt->execute([
                $name,
                $subject,
                $bodyHtml,
                $bodyText,
                json_encode($variables),
                $createdBy
            ]);
            
            if ($success) {
                unset($this->cache[$name]); // Clear cache
                log_info("Email template created/updated: {$name}", 'email');
            }
            
            return $success;
            
        } catch (Exception $e) {
            log_error("Error creating email template: " . $e->getMessage(), 'email');
            return false;
        }
    }
    
    /**
     * Get all templates
     */
    public function getAllTemplates(): array {
        try {
            $stmt = $this->db->query("
                SELECT * FROM email_templates 
                ORDER BY template_name
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            log_error("Error getting all email templates: " . $e->getMessage(), 'email');
            return [];
        }
    }
    
    /**
     * Delete template
     */
    public function deleteTemplate(string $name): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM email_templates WHERE template_name = ?");
            $success = $stmt->execute([$name]);
            
            if ($success) {
                unset($this->cache[$name]);
                log_info("Email template deleted: {$name}", 'email');
            }
            
            return $success;
        } catch (Exception $e) {
            log_error("Error deleting email template: " . $e->getMessage(), 'email');
            return false;
        }
    }
}

/**
 * Email template helper functions
 */

function email_templates(): EmailTemplateSystem {
    return EmailTemplateSystem::getInstance();
}

function send_template_email(string $to, string $templateName, array $variables = [], string $toName = ''): bool {
    return email_templates()->sendEmail($to, $templateName, $variables, $toName);
}

function render_email_template(string $templateName, array $variables = []): ?array {
    return email_templates()->render($templateName, $variables);
}

/**
 * Predefined email sending functions
 */

function send_welcome_email(array $user): bool {
    return send_template_email(
        $user['email'],
        'welcome',
        [
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ],
        $user['first_name'] . ' ' . $user['last_name']
    );
}

function send_registration_confirmation_email(array $user, array $registration): bool {
    return send_template_email(
        $user['email'],
        'registration_confirmation',
        [
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'registration_number' => $registration['registration_number'],
            'category_name' => $registration['category_name'],
            'category_distance' => $registration['category_distance'],
            'amount' => formatCurrency($registration['amount']),
            'payment_status' => $registration['payment_status']
        ],
        $user['first_name'] . ' ' . $user['last_name']
    );
}

function send_payment_confirmation_email(array $user, array $registration): bool {
    return send_template_email(
        $user['email'],
        'payment_confirmation',
        [
            'first_name' => $user['first_name'],
            'registration_number' => $registration['registration_number'],
            'category_name' => $registration['category_name'],
            'amount' => formatCurrency($registration['amount']),
            'payment_method' => $registration['payment_method'],
            'payment_reference' => $registration['payment_reference']
        ],
        $user['first_name'] . ' ' . $user['last_name']
    );
}

function send_payment_reminder_email(array $user, array $registration): bool {
    if (!check_email_rate_limit($user['email'])) {
        log_warning("Email rate limit exceeded for: {$user['email']}", 'email');
        return false;
    }
    
    return send_template_email(
        $user['email'],
        'payment_reminder',
        [
            'first_name' => $user['first_name'],
            'registration_number' => $registration['registration_number'],
            'category_name' => $registration['category_name'],
            'amount' => formatCurrency($registration['amount']),
            'days_remaining' => getDaysUntilDeadline()
        ],
        $user['first_name'] . ' ' . $user['last_name']
    );
}

function send_race_pack_reminder_email(array $user, array $registration): bool {
    return send_template_email(
        $user['email'],
        'race_pack_reminder',
        [
            'first_name' => $user['first_name'],
            'registration_number' => $registration['registration_number'],
            'category_name' => $registration['category_name'],
            'collection_dates' => 'October 9-10, 2025'
        ],
        $user['first_name'] . ' ' . $user['last_name']
    );
}

function send_race_day_reminder_email(array $user, array $registration): bool {
    return send_template_email(
        $user['email'],
        'race_day_reminder',
        [
            'first_name' => $user['first_name'],
            'registration_number' => $registration['registration_number'],
            'category_name' => $registration['category_name'],
            'start_time' => '7:00 AM',
            'checkin_time' => '6:00 AM'
        ],
        $user['first_name'] . ' ' . $user['last_name']
    );
}

/**
 * Initialize default email templates if they don't exist
 */
function initialize_email_templates(): void {
    $templates = email_templates();
    
    // Welcome template
    $templates->createTemplate(
        'welcome',
        'Welcome to {{site_name}}!',
        '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: #4B5320; color: white; padding: 20px; text-align: center;">
                <h1>Welcome to {{site_name}}!</h1>
            </div>
            <div style="padding: 20px;">
                <p>Hello {{first_name}},</p>
                <p>Welcome to Buffalo Marathon 2025! We\'re excited to have you join our running community.</p>
                <p><strong>Event Details:</strong></p>
                <ul>
                    <li>Date: {{marathon_date}} at {{marathon_time}}</li>
                    <li>Venue: {{venue}}</li>
                    <li>Address: {{address}}, {{city}}</li>
                </ul>
                <p>Registration closes on {{registration_deadline}}. Don\'t miss out!</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{site_url}}/categories.php" style="background: #4B5320; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">View Race Categories</a>
                </div>
                <p>If you have any questions, feel free to contact us at {{site_email}}.</p>
                <p>Best regards,<br>Buffalo Marathon Team</p>
            </div>
        </div>
        ',
        'Welcome to {{site_name}}! Hello {{first_name}}, we\'re excited to have you join Buffalo Marathon 2025.',
        ['first_name', 'last_name']
    );
    
    // Registration confirmation template
    $templates->createTemplate(
        'registration_confirmation',
        'Registration Confirmed - {{site_name}}',
        '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: #4B5320; color: white; padding: 20px; text-align: center;">
                <h1>Registration Confirmed!</h1>
            </div>
            <div style="padding: 20px;">
                <p>Hello {{first_name}},</p>
                <p>Your registration for Buffalo Marathon 2025 has been confirmed!</p>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h3>Registration Details:</h3>
                    <p><strong>Registration Number:</strong> {{registration_number}}</p>
                    <p><strong>Category:</strong> {{category_name}} ({{category_distance}})</p>
                    <p><strong>Amount:</strong> {{amount}}</p>
                    <p><strong>Payment Status:</strong> {{payment_status}}</p>
                </div>
                <p><strong>Important Dates:</strong></p>
                <ul>
                    <li>Race Pack Collection: October 9-10, 2025</li>
                    <li>Race Day: {{marathon_date}} at {{marathon_time}}</li>
                </ul>
                <p>Please keep this email for your records. You\'ll need your registration number for race pack collection.</p>
                <p>Best regards,<br>Buffalo Marathon Team</p>
            </div>
        </div>
        ',
        'Registration Confirmed! Hello {{first_name}}, your registration for {{category_name}} has been confirmed.',
        ['first_name', 'registration_number', 'category_name', 'category_distance', 'amount', 'payment_status']
    );
    
    // Payment reminder template
    $templates->createTemplate(
        'payment_reminder',
        'Payment Reminder - {{site_name}}',
        '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: #ff6b35; color: white; padding: 20px; text-align: center;">
                <h1>Payment Reminder</h1>
            </div>
            <div style="padding: 20px;">
                <p>Hello {{first_name}},</p>
                <p>This is a friendly reminder that your registration payment is still pending.</p>
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
                    <h3>Payment Required:</h3>
                    <p><strong>Registration Number:</strong> {{registration_number}}</p>
                    <p><strong>Category:</strong> {{category_name}}</p>
                    <p><strong>Amount Due:</strong> {{amount}}</p>
                </div>
                <p>Only {{days_remaining}} days remaining until registration closes!</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{site_url}}/dashboard.php" style="background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">Complete Payment</a>
                </div>
                <p>If you have already made payment, please disregard this message.</p>
                <p>Best regards,<br>Buffalo Marathon Team</p>
            </div>
        </div>
        ',
        'Payment Reminder - Hello {{first_name}}, your registration payment for {{category_name}} is still pending.',
        ['first_name', 'registration_number', 'category_name', 'amount', 'days_remaining']
    );
}

// Initialize templates on first load
if (mt_rand(1, 100) === 1) {
    try {
        initialize_email_templates();
    } catch (Exception $e) {
        log_error("Error initializing email templates: " . $e->getMessage(), 'email');
    }
}
?>
<?php
/**
 * Buffalo Marathon 2025 - Payment Processing
 * Updated: August 13, 2025
 * Includes ZANACO bank details and mobile money options
 */

if (!defined('BUFFALO_SECURE_ACCESS')) {
    die('Direct access denied');
}

// Include functions for email functionality
require_once __DIR__ . '/functions.php';
// Ensure sendEmail function exists, or define a fallback
if (!function_exists('sendEmail')) {
    /**
     * Basic sendEmail fallback if not defined in functions.php
     */
    function sendEmail($to, $subject, $message, $recipientName = '') {
        $headers = "From: Buffalo Marathon <noreply@buffalomarathon.com>\r\n";
        $headers .= "Reply-To: noreply@buffalomarathon.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        mail($to, $subject, $message, $headers);
    }
}

class PaymentManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get available payment methods with details
     */
    public function getPaymentMethods() {
        return [
            'bank_transfer' => [
                'name' => 'Bank Transfer',
                'enabled' => BANK_TRANSFER_ENABLED,
                'details' => [
                    'bank_name' => BANK_NAME,
                    'account_name' => BANK_ACCOUNT_NAME,
                    'account_number' => BANK_ACCOUNT_NUMBER,
                    'branch' => BANK_BRANCH,
                    'swift_code' => BANK_SWIFT_CODE,
                    'currency' => BANK_CURRENCY,
                    'instructions' => 'Transfer the exact amount and use your registration number as reference. Upload proof of payment.'
                ]
            ],
            'mobile_money' => [
                'name' => 'Mobile Money',
                'enabled' => MOBILE_MONEY_ENABLED,
                'networks' => [
                    'mtn' => [
                        'name' => 'MTN Mobile Money',
                        'enabled' => MTN_ENABLED,
                        'shortcode' => MTN_SHORTCODE,
                        'instructions' => 'Send money to ' . MTN_SHORTCODE . ' using your registration number as reference'
                    ],
                    'airtel' => [
                        'name' => 'Airtel Money',
                        'enabled' => AIRTEL_ENABLED,
                        'shortcode' => AIRTEL_SHORTCODE,
                        'instructions' => 'Send money to ' . AIRTEL_SHORTCODE . ' using your registration number as reference'
                    ],
                    'zamtel' => [
                        'name' => 'Zamtel Kwacha',
                        'enabled' => ZAMTEL_ENABLED,
                        'shortcode' => ZAMTEL_SHORTCODE,
                        'instructions' => 'Send money to ' . ZAMTEL_SHORTCODE . ' using your registration number as reference'
                    ]
                ]
            ],
            'cash' => [
                'name' => 'Cash Payment',
                'enabled' => CASH_PAYMENT_ENABLED,
                'details' => [
                    'location' => EVENT_VENUE,
                    'address' => EVENT_ADDRESS,
                    'hours' => 'Monday - Friday: 8:00 AM - 5:00 PM, Saturday: 8:00 AM - 1:00 PM',
                    'contact' => CONTACT_PHONE_PRIMARY,
                    'instructions' => 'Visit our office with your registration number for cash payment'
                ]
            ]
        ];
    }
    
    /**
     * Create payment record
     */
    public function createPayment($registrationId, $userId, $amount, $method, $details = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO payments (
                    registration_id, user_id, amount, currency, payment_method, 
                    phone_number, network_provider, reference_number, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $phoneNumber = $details['phone_number'] ?? null;
            $networkProvider = $details['network_provider'] ?? null;
            $referenceNumber = $details['reference_number'] ?? null;
            
            return $stmt->execute([
                $registrationId, $userId, $amount, BANK_CURRENCY, $method,
                $phoneNumber, $networkProvider, $referenceNumber
            ]);
        } catch (Exception $e) {
            error_log("Payment creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($registrationId, $status, $adminId = null) {
        try {
            $this->db->beginTransaction();
            
            // Update registration payment status
            $stmt = $this->db->prepare("
                UPDATE registrations 
                SET payment_status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $registrationId]);
            
            // Update payment record
            $paymentStmt = $this->db->prepare("
                UPDATE payments 
                SET status = ?, processed_by = ?, processed_at = NOW() 
                WHERE registration_id = ?
            ");
            $paymentStmt->execute([$status, $adminId, $registrationId]);
            
            // Log the change for audit
            if ($adminId) {
                $logStmt = $this->db->prepare("
                    INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, created_at) 
                    VALUES (?, 'payment_status_update', 'registration', ?, ?, NOW())
                ");
                $logStmt->execute([$adminId, $registrationId, "Payment status changed to: $status"]);
            }
            
            $this->db->commit();
            
            // Send notification email
            $this->sendPaymentNotification($registrationId, $status);
            
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Payment status update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send payment notification email
     */
    private function sendPaymentNotification($registrationId, $status) {
        try {
            // Get registration and user details
            $stmt = $this->db->prepare("
                SELECT r.registration_number, r.amount_paid, u.first_name, u.last_name, u.email, c.name as category_name
                FROM registrations r
                JOIN users u ON r.user_id = u.id
                JOIN categories c ON r.category_id = c.id
                WHERE r.id = ?
            ");
            $stmt->execute([$registrationId]);
            $data = $stmt->fetch();
            
            if ($data) {
                switch($status) {
                    case 'paid':
                        $subject = 'Payment Confirmed - Buffalo Marathon 2025';
                        break;
                    case 'failed':
                        $subject = 'Payment Issue - Buffalo Marathon 2025';
                        break;
                    case 'refunded':
                        $subject = 'Payment Refunded - Buffalo Marathon 2025';
                        break;
                    default:
                        $subject = 'Payment Update - Buffalo Marathon 2025';
                        break;
                }
                
                $message = $this->getPaymentEmailTemplate($data, $status);
                
                // Send email using configured SMTP - include functions.php for sendEmail function
                if (function_exists('sendEmail')) {
                    sendEmail($data['email'], $subject, $message, $data['first_name'] . ' ' . $data['last_name']);
                }
            }
        } catch (Exception $e) {
            error_log("Payment notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Get payment email template
     */
    private function getPaymentEmailTemplate($data, $status) {
        $bankDetails = "
        ZANACO Bank Details:
        Account Name: " . BANK_ACCOUNT_NAME . "
        Account Number: " . BANK_ACCOUNT_NUMBER . "
        Branch: " . BANK_BRANCH . "
        SWIFT Code: " . BANK_SWIFT_CODE . "
        ";
        
        switch($status) {
            case 'paid':
                return "
                    Dear {$data['first_name']} {$data['last_name']},
                    
                    Your payment for Buffalo Marathon 2025 has been confirmed!
                    
                    Registration Details:
                    - Registration Number: {$data['registration_number']}
                    - Category: {$data['category_name']}
                    - Amount Paid: K{$data['amount_paid']}
                    - Status: Payment Confirmed
                    
                    You will receive your race packet details closer to the event date.
                    
                    Thank you for registering for Buffalo Marathon 2025!
                    
                    Contact: " . CONTACT_PHONES_ALL . "
                    Email: " . SITE_EMAIL . "
                ";
                
            case 'failed':
                return "
                    Dear {$data['first_name']} {$data['last_name']},
                    
                    We encountered an issue with your payment for Buffalo Marathon 2025.
                    
                    Registration Number: {$data['registration_number']}
                    
                    Please try again or use alternative payment method:
                    
                    $bankDetails
                    
                    Mobile Money Options:
                    - MTN: " . MTN_SHORTCODE . "
                    - Airtel: " . AIRTEL_SHORTCODE . "
                    - Zamtel: " . ZAMTEL_SHORTCODE . "
                    
                    Contact us for assistance: " . CONTACT_PHONES_ALL . "
                ";
                
            default:
                return "
                    Dear {$data['first_name']} {$data['last_name']},
                    
                    Your payment status for Buffalo Marathon 2025 has been updated.
                    
                    Registration Number: {$data['registration_number']}
                    Status: " . ucfirst($status) . "
                    
                    Contact: " . CONTACT_PHONES_ALL . "
                ";
        }
    }
    
    /**
     * Get payment statistics for admin dashboard
     */
    public function getPaymentStats() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount
                FROM payments 
                WHERE status = 'completed'
                GROUP BY payment_method
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Payment stats error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Generate payment instructions based on method
 */
function getPaymentInstructions($method, $registrationNumber, $amount) {
    $paymentManager = new PaymentManager(getDB());
    $methods = $paymentManager->getPaymentMethods();
    
    if (!isset($methods[$method]) || !$methods[$method]['enabled']) {
        return "Payment method not available.";
    }
    
    $instructions = "Registration: $registrationNumber | Amount: K" . number_format($amount, 2) . "\n\n";
    
    switch($method) {
        case 'bank_transfer':
            $bank = $methods['bank_transfer']['details'];
            $instructions .= "ZANACO Bank Transfer Details:\n";
            $instructions .= "Bank: {$bank['bank_name']}\n";
            $instructions .= "Account Name: {$bank['account_name']}\n";
            $instructions .= "Account Number: {$bank['account_number']}\n";
            $instructions .= "Branch: {$bank['branch']}\n";
            $instructions .= "SWIFT Code: {$bank['swift_code']}\n";
            $instructions .= "Currency: {$bank['currency']}\n\n";
            $instructions .= "IMPORTANT: Use your registration number ($registrationNumber) as the reference.";
            break;
            
        case 'mobile_money':
            $instructions .= "Mobile Money Options:\n";
            foreach($methods['mobile_money']['networks'] as $network) {
                if ($network['enabled']) {
                    $instructions .= "• {$network['name']}: Send to {$network['shortcode']}\n";
                }
            }
            $instructions .= "\nUse registration number ($registrationNumber) as reference.";
            break;
            
        case 'cash':
            $cash = $methods['cash']['details'];
            $instructions .= "Cash Payment:\n";
            $instructions .= "Location: {$cash['location']}\n";
            $instructions .= "Address: {$cash['address']}\n";
            $instructions .= "Hours: {$cash['hours']}\n";
            $instructions .= "Contact: {$cash['contact']}\n";
            break;
    }
    
    return $instructions;
}
?>
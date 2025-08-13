<?php
/**
 * Buffalo Marathon 2025 - Email Queue Processor
 * Background email processing for better performance
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once '../includes/functions.php';

class EmailQueueProcessor {
    private $db;
    private $batch_size = 10;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Process pending emails in queue
     */
    public function processPendingEmails() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM email_queue 
                WHERE status = 'pending' 
                AND (retry_count < 3 OR retry_count IS NULL)
                ORDER BY priority DESC, created_at ASC 
                LIMIT ?
            ");
            $stmt->execute([$this->batch_size]);
            $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            $failed = 0;
            
            foreach ($emails as $email) {
                if ($this->sendEmail($email)) {
                    $this->markEmailSent($email['id']);
                    $processed++;
                } else {
                    $this->markEmailFailed($email['id']);
                    $failed++;
                }
            }
            
            return [
                'processed' => $processed,
                'failed' => $failed,
                'total' => count($emails)
            ];
            
        } catch (Exception $e) {
            error_log("Email queue processing error: " . $e->getMessage());
            return ['processed' => 0, 'failed' => 0, 'total' => 0];
        }
    }
    
    /**
     * Send individual email
     */
    private function sendEmail($email_data) {
        try {
            // Use the existing sendEmail function
            return sendEmail(
                $email_data['recipient_email'],
                $email_data['subject'],
                $email_data['body'],
                $email_data['recipient_name']
            );
            
        } catch (Exception $e) {
            error_log("Email send error for queue ID {$email_data['id']}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark email as sent
     */
    private function markEmailSent($email_id) {
        $stmt = $this->db->prepare("
            UPDATE email_queue 
            SET status = 'sent', sent_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$email_id]);
    }
    
    /**
     * Mark email as failed and increment retry count
     */
    private function markEmailFailed($email_id) {
        $stmt = $this->db->prepare("
            UPDATE email_queue 
            SET status = 'failed', 
                retry_count = COALESCE(retry_count, 0) + 1,
                last_attempt = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$email_id]);
    }
    
    /**
     * Add email to queue
     */
    public static function queueEmail($recipient_email, $recipient_name, $subject, $body, $priority = 'normal') {
        try {
            $db = getDB();
            $priority_value = [
                'high' => 3,
                'normal' => 2,
                'low' => 1
            ][$priority] ?? 2;
            
            $stmt = $db->prepare("
                INSERT INTO email_queue (recipient_email, recipient_name, subject, body, priority, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $recipient_email,
                $recipient_name,
                $subject,
                $body,
                $priority_value
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Email queue error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats() {
        try {
            $stats = [];
            
            $stmt = $this->db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
            $stats['pending'] = $stmt->fetchColumn();
            
            $stmt = $this->db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'sent' AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stats['sent_today'] = $stmt->fetchColumn();
            
            $stmt = $this->db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'failed'");
            $stats['failed'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Queue stats error: " . $e->getMessage());
            return ['pending' => 0, 'sent_today' => 0, 'failed' => 0];
        }
    }
    
    /**
     * Clean old emails from queue
     */
    public function cleanOldEmails($days = 30) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM email_queue 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
                AND status IN ('sent', 'failed')
            ");
            $stmt->execute([$days]);
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Email cleanup error: " . $e->getMessage());
            return 0;
        }
    }
}

// If running from command line, process emails
if (php_sapi_name() === 'cli') {
    $processor = new EmailQueueProcessor();
    $result = $processor->processPendingEmails();
    
    echo "Email Queue Processing Results:\n";
    echo "Processed: {$result['processed']}\n";
    echo "Failed: {$result['failed']}\n";
    echo "Total: {$result['total']}\n";
    
    // Clean old emails weekly
    if (date('w') == 0) { // Sunday
        $cleaned = $processor->cleanOldEmails();
        echo "Cleaned old emails: $cleaned\n";
    }
}
?>

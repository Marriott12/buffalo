<?php
// Basic payment status management
class PaymentManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function updatePaymentStatus($registrationId, $status, $adminId) {
        $stmt = $this->db->prepare("
            UPDATE registrations 
            SET payment_status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$status, $registrationId]);
        
        if ($result) {
            // Log the change
            $logStmt = $this->db->prepare("
                INSERT INTO payment_logs (registration_id, old_status, new_status, changed_by, changed_at) 
                VALUES (?, (SELECT payment_status FROM registrations WHERE id = ?), ?, ?, NOW())
            ");
            $logStmt->execute([$registrationId, $registrationId, $status, $adminId]);
            
            // Send notification email
            $this->sendPaymentNotification($registrationId, $status);
        }
        
        return $result;
    }
    
    private function sendPaymentNotification($registrationId, $status) {
        // Implementation for email notification
        // Get user details and send appropriate email
    }
}
?>
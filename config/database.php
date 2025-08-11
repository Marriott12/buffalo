<?php
/**
 * Buffalo Marathon 2025 - Database Connection Class
 * Production Ready with Error Handling and Security
 */

// Security check
if (!defined('BUFFALO_CONFIG_LOADED')) {
    die('Configuration not loaded');
}

class Database {
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private string $charset;
    private ?PDO $conn = null;
    private static ?Database $instance = null;

    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }

    /**
     * Singleton pattern - only one database connection
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection with comprehensive error handling
     */
    public function getConnection(): PDO {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                PDO::ATTR_TIMEOUT => 30
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Log successful connection
            error_log("Database connection established successfully");
            
            return $this->conn;
            
        } catch (PDOException $e) {
            $error_msg = "Database connection failed: " . $e->getMessage();
            error_log($error_msg);
            
            if (DEBUG_MODE) {
                die("<div style='background: #ffebee; border: 1px solid #f44336; padding: 20px; margin: 20px; border-radius: 5px; font-family: Arial, sans-serif;'>
                    <h3 style='color: #d32f2f; margin: 0 0 10px 0;'>🚨 Database Connection Error</h3>
                    <p style='margin: 0 0 15px 0; font-family: monospace; background: #fff; padding: 10px; border-radius: 3px;'>{$error_msg}</p>
                    <h4 style='color: #d32f2f; margin: 15px 0 10px 0;'>✅ Steps to Fix:</h4>
                    <ol style='margin: 0; padding-left: 20px;'>
                        <li><strong>Check Database Server:</strong> Ensure MySQL/MariaDB is running</li>
                        <li><strong>Verify Credentials:</strong> Update DB_USER and DB_PASS in config.php</li>
                        <li><strong>Create Database:</strong> <code>CREATE DATABASE buffalo_marathon;</code></li>
                        <li><strong>Import Schema:</strong> Run buffalo_marathon_schema.sql</li>
                        <li><strong>Check Permissions:</strong> Ensure database user has proper privileges</li>
                    </ol>
                    <p style='margin: 15px 0 0 0; font-size: 14px; color: #666;'>Current Host: {$this->host} | Database: {$this->db_name} | User: {$this->username}</p>
                    </div>");
            } else {
                // Production error page
                http_response_code(503);
                include __DIR__ . '/../public/error-503.html';
                exit;
            }
        }
    }

    /**
     * Test database connection
     */
    public function testConnection(): bool {
        try {
            $this->getConnection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get database statistics
     */
    public function getStats(): array {
        try {
            $pdo = $this->getConnection();
            
            $stats = [];
            
            // Total users
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
            $stats['total_users'] = $stmt->fetch()['total'];
            
            // Total registrations
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE payment_status != 'cancelled'");
            $stats['total_registrations'] = $stmt->fetch()['total'];
            
            // Confirmed payments
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'confirmed'");
            $stats['confirmed_payments'] = $stmt->fetch()['total'];
            
            // Total revenue
            $stmt = $pdo->query("SELECT SUM(payment_amount) as total FROM registrations WHERE payment_status = 'confirmed'");
            $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
            
            // Recent registrations (last 24 hours)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stats['recent_registrations'] = $stmt->fetch()['total'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting database stats: " . $e->getMessage());
            return [
                'total_users' => 0,
                'total_registrations' => 0,
                'confirmed_payments' => 0,
                'total_revenue' => 0,
                'recent_registrations' => 0
            ];
        }
    }

    /**
     * Check if database tables exist
     */
    public function tablesExist(): array {
        try {
            $pdo = $this->getConnection();
            $required_tables = [
                'users', 'categories', 'registrations', 'schedules', 
                'announcements', 'payment_logs', 'activity_logs', 
                'email_queue', 'settings'
            ];
            
            $existing_tables = [];
            $missing_tables = [];
            
            foreach ($required_tables as $table) {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                
                if ($stmt->rowCount() > 0) {
                    $existing_tables[] = $table;
                } else {
                    $missing_tables[] = $table;
                }
            }
            
            return [
                'existing' => $existing_tables,
                'missing' => $missing_tables,
                'all_exist' => empty($missing_tables)
            ];
            
        } catch (Exception $e) {
            error_log("Error checking tables: " . $e->getMessage());
            return [
                'existing' => [],
                'missing' => [],
                'all_exist' => false
            ];
        }
    }

    /**
     * Close connection
     */
    public function close(): void {
        $this->conn = null;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool {
        return $this->getConnection()->rollBack();
    }
}

/**
 * Global function to get database instance
 */
function getDB(): PDO {
    return Database::getInstance()->getConnection();
}

/**
 * Global function to get database statistics
 */
function getDBStats(): array {
    return Database::getInstance()->getStats();
}
?>
<?php
/**
 * Buffalo Marathon 2025 - Database Connection
 * Production Ready with Error Handling
 */

// Security check
if (!defined('BUFFALO_SECURE_ACCESS')) {
    die('Direct access denied');
}

/**
 * Get database connection
 * @return PDO|null Database connection
 */
function getDatabaseConnection2() {
    static $db = null;
    
    if ($db === null) {
        try {
            $db = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    return $db;
}

/**
 * Execute a prepared statement safely
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return PDOStatement
 */
function executeQuery(string $sql, array $params = []): PDOStatement {
    $db = getDatabase();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Get single record
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return array|false
 */
function getRecord(string $sql, array $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Get multiple records
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return array
 */
function getRecords(string $sql, array $params = []): array {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get single value
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return mixed
 */
function getValue(string $sql, array $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Insert record and return ID
 * @param string $table Table name
 * @param array $data Data to insert
 * @return string Last insert ID
 */
function insertRecord(string $table, array $data): string {
    $columns = array_keys($data);
    $placeholders = array_map(function($col) { return ":$col"; }, $columns);
    
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    
    $db = getDatabase();
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    
    return $db->lastInsertId();
}

/**
 * Update records
 * @param string $table Table name
 * @param array $data Data to update
 * @param string $where WHERE clause
 * @param array $whereParams WHERE parameters
 * @return int Number of affected rows
 */
function updateRecord(string $table, array $data, string $where, array $whereParams = []): int {
    $setParts = array_map(function($col) { return "`$col` = :$col"; }, array_keys($data));
    $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE $where";
    
    $params = array_merge($data, $whereParams);
    $stmt = executeQuery($sql, $params);
    
    return $stmt->rowCount();
}

/**
 * Delete records
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters
 * @return int Number of affected rows
 */
function deleteRecord(string $table, string $where, array $params = []): int {
    $sql = "DELETE FROM `$table` WHERE $where";
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Check if table exists
 * @param string $table Table name
 * @return bool
 */
function tableExists(string $table): bool {
    try {
        $sql = "SELECT 1 FROM `$table` LIMIT 1";
        executeQuery($sql);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get database statistics
 * @return array
 */
function getDBStats(): array {
    $stats = [
        'total_registrations' => 0,
        'confirmed_payments' => 0,
        'pending_payments' => 0,
        'total_users' => 0,
        'active_categories' => 0
    ];
    
    try {
        if (tableExists('registrations')) {
            $stats['total_registrations'] = (int)getValue("SELECT COUNT(*) FROM registrations");
            $stats['confirmed_payments'] = (int)getValue("SELECT COUNT(*) FROM registrations WHERE payment_status = 'paid'");
            $stats['pending_payments'] = (int)getValue("SELECT COUNT(*) FROM registrations WHERE payment_status = 'pending'");
        }
        
        if (tableExists('users')) {
            $stats['total_users'] = (int)getValue("SELECT COUNT(*) FROM users WHERE is_active = 1");
        }
        
        if (tableExists('categories')) {
            $stats['active_categories'] = (int)getValue("SELECT COUNT(*) FROM categories WHERE is_active = 1");
        }
        
    } catch (Exception $e) {
        error_log("Error getting DB stats: " . $e->getMessage());
    }
    
    return $stats;
}

// Initialize global database connection for backward compatibility
try {
    $db = getDatabase();
} catch (Exception $e) {
    // Database not available - this is OK for some operations
    $db = null;
    if (ENVIRONMENT !== 'production') {
        error_log("Database initialization failed: " . $e->getMessage());
    }
}
?>

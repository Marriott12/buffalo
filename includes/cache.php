<?php
/**
 * Buffalo Marathon 2025 - Caching System
 * High-performance caching with database and file fallback
 * Created: 2025-01-09
 */

// Security check
if (!defined('BUFFALO_CONFIG_LOADED')) {
    define('BUFFALO_SECURE_ACCESS', true);
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
}

class BuffaloCache {
    private static ?BuffaloCache $instance = null;
    private PDO $db;
    private string $cacheDir;
    private bool $useDatabase;
    
    private function __construct() {
        $this->db = getDB();
        $this->cacheDir = __DIR__ . '/../cache/';
        $this->useDatabase = CACHE_ENABLED;
        
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance(): BuffaloCache {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get cached value
     */
    public function get(string $key): mixed {
        if (!$this->useDatabase) {
            return null;
        }
        
        try {
            // Try database cache first
            $value = $this->getFromDatabase($key);
            if ($value !== null) {
                return $value;
            }
            
            // Fallback to file cache
            return $this->getFromFile($key);
            
        } catch (Exception $e) {
            error_log("Cache get error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Set cached value
     */
    public function set(string $key, mixed $value, int $ttl = null): bool {
        if (!$this->useDatabase) {
            return false;
        }
        
        $ttl = $ttl ?? CACHE_LIFETIME;
        $expiresAt = new DateTime();
        $expiresAt->add(new DateInterval("PT{$ttl}S"));
        
        try {
            // Store in database
            $success = $this->setInDatabase($key, $value, $expiresAt);
            
            // Also store in file as backup
            $this->setInFile($key, $value, $ttl);
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Cache set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete cached value
     */
    public function delete(string $key): bool {
        try {
            $this->deleteFromDatabase($key);
            $this->deleteFromFile($key);
            return true;
        } catch (Exception $e) {
            error_log("Cache delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear(): bool {
        try {
            // Clear database cache
            $stmt = $this->db->prepare("DELETE FROM cache");
            $stmt->execute();
            
            // Clear file cache
            $files = glob($this->cacheDir . '*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Cache clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get from database cache
     */
    private function getFromDatabase(string $key): mixed {
        $stmt = $this->db->prepare("
            SELECT cache_value 
            FROM cache 
            WHERE cache_key = ? AND expires_at > NOW()
        ");
        $stmt->execute([$key]);
        
        $result = $stmt->fetchColumn();
        if ($result === false) {
            return null;
        }
        
        $value = unserialize($result);
        return $value !== false ? $value : null;
    }
    
    /**
     * Set in database cache
     */
    private function setInDatabase(string $key, mixed $value, DateTime $expiresAt): bool {
        $stmt = $this->db->prepare("
            INSERT INTO cache (cache_key, cache_value, expires_at) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            cache_value = VALUES(cache_value), 
            expires_at = VALUES(expires_at),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $key,
            serialize($value),
            $expiresAt->format('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Delete from database cache
     */
    private function deleteFromDatabase(string $key): bool {
        $stmt = $this->db->prepare("DELETE FROM cache WHERE cache_key = ?");
        return $stmt->execute([$key]);
    }
    
    /**
     * Get from file cache (fallback)
     */
    private function getFromFile(string $key): mixed {
        $filename = $this->cacheDir . md5($key) . '.cache';
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($filename));
        
        if ($data['expires'] < time()) {
            unlink($filename);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set in file cache (fallback)
     */
    private function setInFile(string $key, mixed $value, int $ttl): bool {
        $filename = $this->cacheDir . md5($key) . '.cache';
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($filename, serialize($data)) !== false;
    }
    
    /**
     * Delete from file cache
     */
    private function deleteFromFile(string $key): bool {
        $filename = $this->cacheDir . md5($key) . '.cache';
        return !file_exists($filename) || unlink($filename);
    }
    
    /**
     * Clean expired cache entries
     */
    public function cleanExpired(): int {
        $cleaned = 0;
        
        try {
            // Clean database cache
            $stmt = $this->db->prepare("DELETE FROM cache WHERE expires_at < NOW()");
            $stmt->execute();
            $cleaned += $stmt->rowCount();
            
            // Clean file cache
            $files = glob($this->cacheDir . '*.cache');
            foreach ($files as $file) {
                $data = unserialize(file_get_contents($file));
                if ($data['expires'] < time()) {
                    unlink($file);
                    $cleaned++;
                }
            }
            
        } catch (Exception $e) {
            error_log("Cache cleanup error: " . $e->getMessage());
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array {
        try {
            $stats = [
                'database_entries' => 0,
                'file_entries' => 0,
                'total_size' => 0,
                'expired_entries' => 0
            ];
            
            // Database stats
            $stmt = $this->db->query("SELECT COUNT(*) FROM cache");
            $stats['database_entries'] = $stmt->fetchColumn();
            
            $stmt = $this->db->query("SELECT COUNT(*) FROM cache WHERE expires_at < NOW()");
            $stats['expired_entries'] = $stmt->fetchColumn();
            
            // File stats
            $files = glob($this->cacheDir . '*.cache');
            $stats['file_entries'] = count($files);
            
            foreach ($files as $file) {
                $stats['total_size'] += filesize($file);
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Cache stats error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Helper functions for easy cache access
 */

function cache_get(string $key): mixed {
    return BuffaloCache::getInstance()->get($key);
}

function cache_set(string $key, mixed $value, int $ttl = null): bool {
    return BuffaloCache::getInstance()->set($key, $value, $ttl);
}

function cache_delete(string $key): bool {
    return BuffaloCache::getInstance()->delete($key);
}

function cache_clear(): bool {
    return BuffaloCache::getInstance()->clear();
}

/**
 * Cache specific data with automatic keys
 */

function cache_categories(): array {
    $key = 'categories_active';
    $categories = cache_get($key);
    
    if ($categories === null) {
        try {
            $db = getDB();
            $stmt = $db->query("
                SELECT c.*, 
                       COALESCE(rs.total_registrations, 0) as registration_count,
                       COALESCE(rs.confirmed_registrations, 0) as confirmed_count,
                       COALESCE(rs.pending_registrations, 0) as pending_count
                FROM categories c
                LEFT JOIN registration_stats rs ON c.id = rs.category_id
                WHERE c.is_active = 1
                ORDER BY c.sort_order, c.name
            ");
            $categories = $stmt->fetchAll();
            
            // Cache for 5 minutes
            cache_set($key, $categories, 300);
            
        } catch (Exception $e) {
            error_log("Error caching categories: " . $e->getMessage());
            return [];
        }
    }
    
    return $categories;
}

function cache_registration_stats(int $categoryId = null): array {
    $key = $categoryId ? "reg_stats_{$categoryId}" : 'reg_stats_all';
    $stats = cache_get($key);
    
    if ($stats === null) {
        try {
            $db = getDB();
            
            if ($categoryId) {
                $stmt = $db->prepare("
                    SELECT * FROM registration_stats WHERE category_id = ?
                ");
                $stmt->execute([$categoryId]);
                $stats = $stmt->fetch() ?: [];
            } else {
                $stmt = $db->query("
                    SELECT 
                        SUM(total_registrations) as total,
                        SUM(confirmed_registrations) as confirmed,
                        SUM(pending_registrations) as pending
                    FROM registration_stats
                ");
                $stats = $stmt->fetch() ?: [];
            }
            
            // Cache for 1 minute
            cache_set($key, $stats, 60);
            
        } catch (Exception $e) {
            error_log("Error caching registration stats: " . $e->getMessage());
            return [];
        }
    }
    
    return $stats;
}

function invalidate_category_cache(): void {
    cache_delete('categories_active');
    
    // Clear all registration stats cache
    $keys = ['reg_stats_all'];
    for ($i = 1; $i <= 10; $i++) {
        $keys[] = "reg_stats_{$i}";
    }
    
    foreach ($keys as $key) {
        cache_delete($key);
    }
}

// Auto-cleanup expired cache entries (runs occasionally)
if (mt_rand(1, 100) === 1) {
    BuffaloCache::getInstance()->cleanExpired();
}
?>
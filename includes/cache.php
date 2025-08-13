<?php
/**
 * Buffalo Marathon 2025 - Cache Management System
 * Improves performance through intelligent caching
 */

if (!defined('BUFFALO_SECURE_ACCESS')) {
    die('Direct access not permitted');
}

class CacheManager {
    private static $cache_dir = __DIR__ . '/../cache';
    private static $default_ttl = 3600; // 1 hour
    
    public static function init() {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
    }
    
    /**
     * Get cached categories with availability
     */
    public static function getCachedCategories() {
        $cache_key = 'categories_with_availability';
        $cached = self::get($cache_key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Generate fresh data
        $db = getDB();
        $stmt = $db->query("
            SELECT c.id, c.name, c.distance, c.description, c.price, c.max_participants,
                   COUNT(r.id) as registered_count,
                   (c.max_participants - COUNT(r.id)) as spots_remaining
            FROM categories c
            LEFT JOIN registrations r ON c.id = r.category_id 
                AND r.payment_status = 'confirmed'
            WHERE c.is_active = 1
            GROUP BY c.id, c.name, c.distance, c.description, c.price, c.max_participants
            ORDER BY FIELD(c.name, 'Full Marathon', 'Half Marathon', 'Power Challenge', 'Family Fun Run', 'VIP Run', 'Kid Run')
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        self::set($cache_key, $categories, 300); // 5 minutes for categories
        return $categories;
    }
    
    /**
     * Get cached statistics for dashboard
     */
    public static function getCachedStats() {
        $cache_key = 'dashboard_stats';
        $cached = self::get($cache_key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $db = getDB();
        
        // Total registrations
        $total_registrations = $db->query("SELECT COUNT(*) FROM registrations WHERE payment_status = 'confirmed'")->fetchColumn();
        
        // Revenue
        $total_revenue = $db->query("SELECT SUM(amount) FROM registrations WHERE payment_status = 'confirmed'")->fetchColumn();
        
        // Categories breakdown
        $category_stats = $db->query("
            SELECT c.name, COUNT(r.id) as count, SUM(r.amount) as revenue
            FROM categories c
            LEFT JOIN registrations r ON c.id = r.category_id AND r.payment_status = 'confirmed'
            GROUP BY c.id, c.name
            ORDER BY count DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total_registrations' => $total_registrations,
            'total_revenue' => $total_revenue,
            'category_breakdown' => $category_stats,
            'cache_time' => time()
        ];
        
        self::set($cache_key, $stats, 600); // 10 minutes for stats
        return $stats;
    }
    
    /**
     * Store data in cache
     */
    public static function set($key, $data, $ttl = null) {
        self::init();
        $ttl = $ttl ?? self::$default_ttl;
        $cache_file = self::$cache_dir . '/' . md5($key) . '.cache';
        
        $cache_data = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($cache_file, serialize($cache_data), LOCK_EX);
    }
    
    /**
     * Get data from cache
     */
    public static function get($key) {
        self::init();
        $cache_file = self::$cache_dir . '/' . md5($key) . '.cache';
        
        if (!file_exists($cache_file)) {
            return null;
        }
        
        $cache_data = unserialize(file_get_contents($cache_file));
        
        if (time() > $cache_data['expires']) {
            unlink($cache_file);
            return null;
        }
        
        return $cache_data['data'];
    }
    
    /**
     * Clear all cache or specific key
     */
    public static function clear($key = null) {
        self::init();
        
        if ($key) {
            $cache_file = self::$cache_dir . '/' . md5($key) . '.cache';
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
        } else {
            $files = glob(self::$cache_dir . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Clear cache when registration status changes
     */
    public static function clearRegistrationCache() {
        self::clear('categories_with_availability');
        self::clear('dashboard_stats');
    }
}

// Auto-clear cache on registration updates
function clearCacheOnRegistrationUpdate() {
    CacheManager::clearRegistrationCache();
}
?>

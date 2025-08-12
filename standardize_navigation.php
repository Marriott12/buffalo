<?php
/**
 * Buffalo Marathon 2025 - Navigation Standardization Script
 * This script ensures all pages use uniform header and footer
 */

define('BUFFALO_SECURE_ACCESS', true);

echo "=== Buffalo Marathon 2025 - Navigation Standardization ===\n";
echo "This script will update all pages to use uniform header and footer\n\n";

// List of PHP files that should use standard header/footer
$pages_to_update = [
    'index.php',
    'categories.php', 
    'schedule.php',
    'info.php',
    'contact.php',
    'faq.php',
    'terms.php',
    'privacy.php',
    'login.php',
    'register.php',
    'register-marathon.php',
    'dashboard.php',
    'profile.php',
    'my-registration.php'
];

// Standard header/footer structure for pages
$header_check_patterns = [
    '<!DOCTYPE html>',
    '<html',
    '<head>',
    '<title>',
    '<body>'
];

$footer_check_patterns = [
    '</body>',
    '</html>'
];

echo "🔍 Checking pages for header/footer standardization...\n\n";

foreach ($pages_to_update as $page) {
    if (!file_exists($page)) {
        echo "⚠️  Page not found: $page\n";
        continue;
    }
    
    $content = file_get_contents($page);
    $needs_header = true;
    $needs_footer = true;
    $has_own_html = false;
    
    // Check if page includes header
    if (strpos($content, "include 'includes/header.php'") !== false || 
        strpos($content, 'include "includes/header.php"') !== false ||
        strpos($content, "require 'includes/header.php'") !== false ||
        strpos($content, 'require "includes/header.php"') !== false) {
        $needs_header = false;
    }
    
    // Check if page includes footer
    if (strpos($content, "include 'includes/footer.php'") !== false || 
        strpos($content, 'include "includes/footer.php"') !== false ||
        strpos($content, "require 'includes/footer.php'") !== false ||
        strpos($content, 'require "includes/footer.php"') !== false) {
        $needs_footer = false;
    }
    
    // Check if page has its own HTML structure
    foreach ($header_check_patterns as $pattern) {
        if (strpos($content, $pattern) !== false) {
            $has_own_html = true;
            break;
        }
    }
    
    echo "📄 $page: ";
    
    if (!$needs_header && !$needs_footer) {
        echo "✅ Already standardized\n";
        continue;
    }
    
    if ($has_own_html && ($needs_header || $needs_footer)) {
        echo "🔄 Needs conversion (has own HTML)\n";
        
        // Create backup
        $backup_file = $page . '.backup.' . date('Y-m-d_H-i-s');
        copy($page, $backup_file);
        echo "   📦 Backup created: $backup_file\n";
        
        // Extract PHP logic and content between <body> tags
        $php_logic = '';
        $body_content = '';
        
        // Extract PHP logic from top of file
        if (preg_match('/^(<\?php.*?\?>)/s', $content, $matches)) {
            $php_logic = $matches[1];
        }
        
        // Extract content between body tags
        if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $content, $matches)) {
            $body_content = $matches[1];
        } else {
            // If no body tags, try to extract content after PHP logic
            $php_end = strpos($content, '?>');
            if ($php_end !== false) {
                $remaining = substr($content, $php_end + 2);
                // Remove DOCTYPE, html, head, and body opening tags
                $remaining = preg_replace('/<!DOCTYPE[^>]*>/i', '', $remaining);
                $remaining = preg_replace('/<html[^>]*>/i', '', $remaining);
                $remaining = preg_replace('/<head>.*?<\/head>/s', '', $remaining);
                $remaining = preg_replace('/<body[^>]*>/i', '', $remaining);
                $remaining = preg_replace('/<\/body>.*?<\/html>/s', '', $remaining);
                $body_content = trim($remaining);
            }
        }
        
        // Create new standardized content
        $new_content = $php_logic . "\n\n";
        $new_content .= "<?php\n";
        $new_content .= "// Set page title and description\n";
        $new_content .= "\$page_title = \$page_title ?? '" . ucfirst(str_replace(['.php', '-'], ['', ' '], $page)) . "';\n";
        $new_content .= "\$page_description = \$page_description ?? 'Buffalo Marathon 2025 - ' . \$page_title;\n";
        $new_content .= "include 'includes/header.php';\n";
        $new_content .= "?>\n\n";
        $new_content .= "<!-- Main Content -->\n";
        $new_content .= $body_content;
        $new_content .= "\n\n<?php include 'includes/footer.php'; ?>\n";
        
        // Write new content (commented out for safety - manual review needed)
        // file_put_contents($page, $new_content);
        
        echo "   ⚠️  Manual conversion needed - backup created\n";
        
    } else {
        if ($needs_header) {
            echo "❌ Missing header include\n";
        }
        if ($needs_footer) {
            echo "❌ Missing footer include\n";
        }
    }
}

echo "\n📋 Navigation Menu Items Status:\n";

// Check for consistent navigation
$nav_items = [
    'Home' => 'index.php',
    'Categories' => 'categories.php', 
    'Schedule' => 'schedule.php',
    'Event Info' => 'info.php',
    'Contact' => 'contact.php',
    'FAQ' => 'faq.php'
];

foreach ($nav_items as $label => $file) {
    if (file_exists($file)) {
        echo "✅ $label ($file)\n";
    } else {
        echo "❌ $label ($file) - Missing\n";
    }
}

echo "\n🔧 Header.php Navigation Review:\n";

// Read header navigation
$header_content = file_get_contents('includes/header.php');

// Check for responsive navigation
if (strpos($header_content, 'navbar-toggler') !== false) {
    echo "✅ Mobile responsive navigation\n";
} else {
    echo "❌ Missing mobile responsive navigation\n";
}

// Check for user authentication states
if (strpos($header_content, 'isLoggedIn()') !== false) {
    echo "✅ User authentication states handled\n";
} else {
    echo "❌ Missing user authentication handling\n";
}

// Check for admin navigation
if (strpos($header_content, 'isAdmin()') !== false) {
    echo "✅ Admin navigation included\n";
} else {
    echo "❌ Missing admin navigation\n";
}

echo "\n💡 Recommendations:\n";
echo "1. Review backup files before applying changes\n";
echo "2. Test navigation on all devices (mobile, tablet, desktop)\n";
echo "3. Ensure all menu items are working correctly\n";
echo "4. Check that user/admin states show appropriate navigation\n";
echo "5. Verify all pages load with consistent styling\n";
echo "6. Test all form submissions work correctly\n";

echo "\n📞 Support: +260 972 545 658 / +260 770 809 062 / +260 771 470 868\n";
echo "🗑️ Delete this script after reviewing and applying changes\n";
?>

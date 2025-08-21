<?php
/**
 * Simple test file to verify project setup
 * Run this file to check if everything is working correctly
 */

echo "<h1>Telegram Live Streaming - Test Page</h1>";

// Check PHP version
echo "<h2>PHP Version Check</h2>";
echo "Current PHP version: " . PHP_VERSION . "<br>";
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo "✅ PHP version is compatible<br>";
} else {
    echo "❌ PHP version must be 8.0 or higher<br>";
}

// Check if Composer autoload exists
echo "<h2>Composer Autoload Check</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✅ Composer autoload found<br>";
} else {
    echo "❌ Composer autoload not found. Run 'composer install'<br>";
}

// Check if .env file exists
echo "<h2>Environment File Check</h2>";
if (file_exists(__DIR__ . '/.env')) {
    echo "✅ .env file found<br>";
} else {
    echo "❌ .env file not found. Copy env.example to .env and configure it<br>";
}

// Check required directories
echo "<h2>Directory Check</h2>";
$directories = ['sessions', 'uploads', 'logs', 'public'];
foreach ($directories as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "✅ $dir directory exists<br>";
    } else {
        echo "❌ $dir directory missing. Create it manually<br>";
    }
}

// Check file permissions
echo "<h2>File Permissions Check</h2>";
$directories = ['sessions', 'uploads', 'logs'];
foreach ($directories as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        if (is_writable(__DIR__ . '/' . $dir)) {
            echo "✅ $dir directory is writable<br>";
        } else {
            echo "❌ $dir directory is not writable<br>";
        }
    }
}

// Check if required extensions are loaded
echo "<h2>PHP Extensions Check</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "❌ $ext extension not loaded<br>";
    }
}

// Try to load classes (if autoload exists)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<h2>Class Loading Test</h2>";
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        // Test Database class
        if (class_exists('TelegramLive\Config\Database')) {
            echo "✅ Database class loaded successfully<br>";
        } else {
            echo "❌ Database class not found<br>";
        }
        
        // Test TelegramManager class
        if (class_exists('TelegramLive\TelegramManager')) {
            echo "✅ TelegramManager class loaded successfully<br>";
        } else {
            echo "❌ TelegramManager class not found<br>";
        }
        
        // Test YouTubeManager class
        if (class_exists('TelegramLive\YouTubeManager')) {
            echo "✅ YouTubeManager class loaded successfully<br>";
        } else {
            echo "❌ YouTubeManager class not found<br>";
        }
        
        // Test LiveStreamManager class
        if (class_exists('TelegramLive\LiveStreamManager')) {
            echo "✅ LiveStreamManager class loaded successfully<br>";
        } else {
            echo "❌ LiveStreamManager class not found<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error loading classes: " . $e->getMessage() . "<br>";
    }
}

// Check web server
echo "<h2>Web Server Check</h2>";
if (isset($_SERVER['SERVER_SOFTWARE'])) {
    echo "Web server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
} else {
    echo "Running from command line<br>";
}

// Check if public/index.php is accessible
echo "<h2>Web Interface Check</h2>";
if (file_exists(__DIR__ . '/public/index.php')) {
    echo "✅ Main web interface file exists<br>";
    echo "Web interface should be accessible at: " . (isset($_SERVER['HTTP_HOST']) ? "http://" . $_SERVER['HTTP_HOST'] . "/public/" : "http://localhost/public/") . "<br>";
} else {
    echo "❌ Main web interface file not found<br>";
}

echo "<h2>Next Steps</h2>";
echo "1. Configure your .env file with API keys<br>";
echo "2. Create MySQL database named 'telegram_live'<br>";
echo "3. Access the web interface to start using the application<br>";
echo "4. Check logs/ directory for any error messages<br>";

echo "<hr>";
echo "<p><strong>Note:</strong> This is a test file. Remove it in production for security reasons.</p>";
?>

<?php
/**
 * Telegram Live Streaming Application
 * Production Version
 */

// Error reporting for production
if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

require_once __DIR__ . '/../vendor/autoload.php';

use TelegramLive\TelegramManager;
use TelegramLive\LiveStreamManager;
use TelegramLive\Database;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize database
try {
    $database = Database::getInstance();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

session_start();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $phone = $_POST['phone'] ?? '';
                if (empty($phone)) {
                    $error = 'Telefon raqamini kiriting';
                } else {
                    try {
                        $telegramManager = new TelegramManager($phone);
                        $result = $telegramManager->authenticate();
                        
                        if ($result['success']) {
                            $_SESSION['phone'] = $phone;
                            $_SESSION['authenticated'] = true;
                            $message = 'Telegram sessiyasi muvaffaqiyatli yaratildi!';
                        } else {
                            $error = $result['message'];
                        }
                    } catch (Exception $e) {
                        $error = 'Xatolik: ' . $e->getMessage();
                    }
                }
                break;

            case 'start_stream':
                if (!isset($_SESSION['authenticated'])) {
                    $error = 'Avval tizimga kirish kerak';
                } else {
                    $channelId = (int)($_POST['channel_id'] ?? 0);
                    $youtubeUrl = $_POST['youtube_url'] ?? '';
                    
                    if (empty($channelId) || empty($youtubeUrl)) {
                        $error = 'Barcha maydonlarni to\'ldiring';
                    } else {
                        try {
                            $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
                            $result = $liveStreamManager->startLiveStream($channelId, $youtubeUrl);
                            
                            if ($result['success']) {
                                $message = 'Live stream muvaffaqiyatli boshladi!';
                            } else {
                                $error = $result['message'];
                            }
                        } catch (Exception $e) {
                            $error = 'Xatolik: ' . $e->getMessage();
                        }
                    }
                }
                break;

            case 'stop_stream':
                if (!isset($_SESSION['authenticated'])) {
                    $error = 'Avval tizimga kirish kerak';
                } else {
                    $channelId = (int)($_POST['channel_id'] ?? 0);
                    
                    if (empty($channelId)) {
                        $error = 'Kanal ID kerak';
                    } else {
                        try {
                            $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
                            $result = $liveStreamManager->stopLiveStream($channelId);
                            
                            if ($result['success']) {
                                $message = 'Live stream to\'xtatildi!';
                            } else {
                                $error = $result['message'];
                            }
                        } catch (Exception $e) {
                            $error = 'Xatolik: ' . $e->getMessage();
                        }
                    }
                }
                break;
        }
    }
}

// Get admin channels if authenticated
$adminChannels = [];
$activeStreams = [];

        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
            try {
                $telegramManager = new TelegramManager($_SESSION['phone']);
                $adminChannels = $telegramManager->getAdminChannels();
                
                $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
                $activeStreams = $liveStreamManager->getAllActiveStreams();
            } catch (Exception $e) {
                $error = 'Kanal ma\'lumotlarini olishda xatolik: ' . $e->getMessage();
                // Reset session if there's an error
                session_destroy();
                session_start();
            }
        }
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Live Streaming</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .channel-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .channel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stream-status {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fab fa-telegram"></i> Telegram Live
            </a>
            <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
                <span class="navbar-text">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['phone']); ?>
                </span>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">
                <i class="fab fa-youtube text-danger"></i> YouTube Live Streams
            </h1>
            <p class="lead mb-4">Telegram kanallaringizga YouTube live streamlarni real-time tarzda uzating</p>
            <?php if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']): ?>
                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="fas fa-sign-in-alt"></i> Boshlash
                </button>
            <?php endif; ?>
        </div>
    </section>

    <div class="container mt-5">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
            <!-- Admin Channels Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <h2 class="mb-4">
                        <i class="fas fa-broadcast-tower"></i> Admin Kanalaringiz
                    </h2>
                    <div class="row">
                        <?php if (empty($adminChannels)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Admin bo'lgan kanallar topilmadi
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($adminChannels as $channel): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card channel-card h-100" onclick="selectChannel(<?php echo $channel['channel_id']; ?>, '<?php echo htmlspecialchars($channel['channel_name']); ?>')">
                                        <div class="card-body text-center">
                                            <i class="fas fa-broadcast-tower fa-3x text-primary mb-3"></i>
                                            <h5 class="card-title"><?php echo htmlspecialchars($channel['channel_name']); ?></h5>
                                            <?php if ($channel['channel_username']): ?>
                                                <p class="card-text text-muted">@<?php echo htmlspecialchars($channel['channel_username']); ?></p>
                                            <?php endif; ?>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-users"></i> 
                                                    <?php echo number_format($channel['participants_count'] ?? 0); ?> a'zo
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Start Stream Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="feature-card">
                        <h3 class="mb-4">
                            <i class="fas fa-play-circle"></i> Live Stream Boshlash
                        </h3>
                        <form id="streamForm" method="POST">
                            <input type="hidden" name="action" value="start_stream">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="channel_id" class="form-label">Kanal</label>
                                    <select class="form-select" id="channel_id" name="channel_id" required>
                                        <option value="">Kanalni tanlang</option>
                                        <?php foreach ($adminChannels as $channel): ?>
                                            <option value="<?php echo $channel['channel_id']; ?>">
                                                <?php echo htmlspecialchars($channel['channel_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="youtube_url" class="form-label">YouTube Live Stream URL</label>
                                    <input type="url" class="form-control" id="youtube_url" name="youtube_url" 
                                           placeholder="https://www.youtube.com/watch?v=..." required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-play"></i> Boshlash
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Active Streams Section -->
            <?php if (!empty($activeStreams)): ?>
                <div class="row mb-5">
                    <div class="col-12">
                        <h3 class="mb-4">
                            <i class="fas fa-broadcast-tower"></i> Faol Live Streamlar
                        </h3>
                        <div class="row">
                            <?php foreach ($activeStreams as $stream): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="stream-status">
                                                <span class="badge bg-success">Faol</span>
                                            </div>
                                            <h6 class="card-title"><?php echo htmlspecialchars($stream['stream_title']); ?></h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-broadcast-tower"></i> 
                                                    <?php echo htmlspecialchars($stream['channel_name']); ?>
                                                </small>
                                            </p>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('d.m.Y H:i', strtotime($stream['created_at'])); ?>
                                                </small>
                                            </p>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="stop_stream">
                                                <input type="hidden" name="channel_id" value="<?php echo $stream['channel_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-stop"></i> To'xtatish
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Features Section -->
            <div class="row mb-5">
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                        <h4>Telegram Kirish</h4>
                        <p>Telefon raqamingiz bilan Telegram sessiyasini yarating</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-broadcast-tower fa-3x text-success mb-3"></i>
                        <h4>Kanal Tanlash</h4>
                        <p>Admin bo'lgan Telegram kanallaringizni tanlang</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-play-circle fa-3x text-danger mb-3"></i>
                        <h4>Live Stream</h4>
                        <p>YouTube live streamlarni kanalingizga uzating</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fab fa-telegram"></i> Telegram Kirish
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefon raqam</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="+998901234567" required>
                            <div class="form-text">
                                Telegram API ID va API Hash kerak bo'ladi
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Kirish
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Utility functions
        function selectChannel(channelId, channelName) {
            document.getElementById('channel_id').value = channelId;
            // Scroll to stream form
            document.getElementById('streamForm').scrollIntoView({ behavior: 'smooth' });
        }

        // Safe JSON parsing function
        function safeJsonParse(jsonString) {
            try {
                return JSON.parse(jsonString);
            } catch (error) {
                console.error('JSON parsing error:', error);
                console.error('Response content:', jsonString);
                return null;
            }
        }

        // Safe fetch function with error handling
        async function safeFetch(url, options = {}) {
            try {
                const response = await fetch(url, options);
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response text first
                const text = await response.text();
                
                // Try to parse as JSON
                const data = safeJsonParse(text);
                
                if (data === null) {
                    throw new Error('Invalid JSON response from server');
                }
                
                return data;
            } catch (error) {
                console.error('Fetch error:', error);
                throw error;
            }
        }

        // API functions
        async function getChannels() {
            try {
                const data = await safeFetch('/api.php?action=channels');
                return data;
            } catch (error) {
                console.error('Failed to get channels:', error);
                return { error: 'Kanal ma\'lumotlarini olishda xatolik' };
            }
        }

        async function getStreams() {
            try {
                const data = await safeFetch('/api.php?action=streams');
                return data;
            } catch (error) {
                console.error('Failed to get streams:', error);
                return { error: 'Stream ma\'lumotlarini olishda xatolik' };
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch (error) {
                    console.error('Error closing alert:', error);
                }
            });
        }, 5000);

        // Global error handler
        window.addEventListener('error', function(event) {
            console.error('Global error:', event.error);
            // Prevent MadelineProto longPollQr errors from showing
            if (event.error && event.error.message && event.error.message.includes('JSON')) {
                event.preventDefault();
                console.log('Suppressed JSON parsing error from MadelineProto');
            }
        });

        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            // Prevent MadelineProto errors from showing
            if (event.reason && event.reason.message && event.reason.message.includes('JSON')) {
                event.preventDefault();
                console.log('Suppressed JSON parsing error from MadelineProto');
            }
        });

        // Block any automatic requests that might cause errors
        const originalFetch = window.fetch;
        window.fetch = function(url, options) {
            // Block MadelineProto web interface requests
            if (typeof url === 'string' && (
                url.includes('waitQrCodeOrLogin') || 
                url.includes('getQrCode') ||
                url.includes('.web.php') ||
                url.includes('.web.html')
            )) {
                console.log('Blocked MadelineProto web interface request:', url);
                return Promise.reject(new Error('Blocked MadelineProto request'));
            }
            return originalFetch.call(this, url, options);
        };

        // Block XMLHttpRequest for MadelineProto
        const originalXHROpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            if (typeof url === 'string' && (
                url.includes('waitQrCodeOrLogin') || 
                url.includes('getQrCode') ||
                url.includes('.web.php') ||
                url.includes('.web.html')
            )) {
                console.log('Blocked MadelineProto XMLHttpRequest:', url);
                this.abort();
                return;
            }
            return originalXHROpen.call(this, method, url, ...args);
        };
    </script>
</body>
</html>

<?php
// Error handling - xatolarni ushlash
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Global error handler
function handleError($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
}

set_error_handler('handleError');

// Exception handler
function handleException($e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Exception occurred',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
}

set_exception_handler('handleException');

require_once __DIR__ . '/../vendor/autoload.php';

use TelegramLive\TelegramManager;
use TelegramLive\LiveStreamManager;
use TelegramLive\Database;

try {

    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    // Initialize database
    try {
        $database = Database::getInstance();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }

    session_start();

    // Handle preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['error' => 'Action parameter is required']);
        exit;
    }

    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fatal error: ' . $e->getMessage()]);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'channels':
            getAdminChannels();
            break;
        case 'streams':
            getActiveStreams();
            break;
        case 'stream_preview':
            getStreamPreview();
            break;
        case 'analytics':
            getStreamAnalytics();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handlePostRequest($action) {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
        case 'start_stream':
            startLiveStream();
            break;
        case 'stop_stream':
            stopLiveStream();
            break;
        case 'search_streams':
            searchLiveStreams();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true);
    $phone = $input['phone'] ?? '';

    if (empty($phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'Phone number is required']);
        return;
    }

    try {
        $telegramManager = new TelegramManager($phone);
        $result = $telegramManager->authenticate();

        if ($result['success']) {
            $_SESSION['phone'] = $phone;
            $_SESSION['authenticated'] = true;
            echo json_encode([
                'success' => true,
                'message' => 'Authentication successful',
                'phone' => $phone
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Authentication failed: ' . $e->getMessage()]);
    }
}

function getAdminChannels() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    try {
        $telegramManager = new TelegramManager($_SESSION['phone']);
        $channels = $telegramManager->getAdminChannels();
        
        echo json_encode([
            'success' => true,
            'channels' => $channels
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get channels: ' . $e->getMessage()]);
    }
}

function startLiveStream() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $channelId = (int)($input['channel_id'] ?? 0);
    $youtubeUrl = $input['youtube_url'] ?? '';

    if (empty($channelId) || empty($youtubeUrl)) {
        http_response_code(400);
        echo json_encode(['error' => 'Channel ID and YouTube URL are required']);
        return;
    }

    try {
        $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
        $result = $liveStreamManager->startLiveStream($channelId, $youtubeUrl);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Live stream started successfully',
                'data' => $result
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to start live stream: ' . $e->getMessage()]);
    }
}

function stopLiveStream() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $channelId = (int)($input['channel_id'] ?? 0);

    if (empty($channelId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Channel ID is required']);
        return;
    }

    try {
        $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
        $result = $liveStreamManager->stopLiveStream($channelId);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Live stream stopped successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to stop live stream: ' . $e->getMessage()]);
    }
}

function getActiveStreams() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    try {
        $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
        $streams = $liveStreamManager->getAllActiveStreams();
        
        echo json_encode([
            'success' => true,
            'streams' => $streams
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get active streams: ' . $e->getMessage()]);
    }
}

function getStreamPreview() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    $youtubeUrl = $_GET['url'] ?? '';
    if (empty($youtubeUrl)) {
        http_response_code(400);
        echo json_encode(['error' => 'YouTube URL is required']);
        return;
    }

    try {
        $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
        $result = $liveStreamManager->getStreamPreview(0, $youtubeUrl); // channelId not needed for preview
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'preview' => $result['preview']
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get stream preview: ' . $e->getMessage()]);
    }
}

function searchLiveStreams() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';
    $maxResults = (int)($input['max_results'] ?? 10);

    if (empty($query)) {
        http_response_code(400);
        echo json_encode(['error' => 'Search query is required']);
        return;
    }

    try {
        $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
        $youtubeManager = $liveStreamManager->getYouTubeManager();
        $streams = $youtubeManager->searchLiveStreams($query, $maxResults);
        
        echo json_encode([
            'success' => true,
            'streams' => $streams
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to search live streams: ' . $e->getMessage()]);
    }
}

function getStreamAnalytics() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    $channelId = (int)($_GET['channel_id'] ?? 0);
    if (empty($channelId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Channel ID is required']);
        return;
    }

    try {
        $liveStreamManager = new LiveStreamManager($_SESSION['phone']);
        $analytics = $liveStreamManager->getStreamAnalytics($channelId);
        
        echo json_encode([
            'success' => true,
            'analytics' => $analytics
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get analytics: ' . $e->getMessage()]);
    }
}

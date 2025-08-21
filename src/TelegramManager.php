<?php

namespace TelegramLive;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Connection;
use danog\MadelineProto\Settings\RPC;
use TelegramLive\Database;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class TelegramManager
{
    private API $madelineProto;
    private Database $database;
    private Logger $logger;
    private string $sessionPath;
    private string $phone;

    public function __construct(string $phone)
    {
        $this->phone = $phone;
        $this->sessionPath = $_ENV['SESSION_PATH'] ?? __DIR__ . '/../../public/sessions/';
        // Ensure the path ends with a directory separator
        if (!str_ends_with($this->sessionPath, DIRECTORY_SEPARATOR)) {
            $this->sessionPath .= DIRECTORY_SEPARATOR;
        }
        $this->database = Database::getInstance();
        $this->setupLogger();
        $this->ensureSessionDirectoryExists();
        $this->initializeMadelineProto();
    }

    private function setupLogger(): void
    {
        $this->logger = new Logger('TelegramManager');
        $logFile = $_ENV['LOG_FILE'] ?? __DIR__ . '/../../logs/telegram.log';
        $this->logger->pushHandler(new StreamHandler($logFile, Logger::INFO));
    }

    private function ensureSessionDirectoryExists(): void
    {
        if (!is_dir($this->sessionPath)) {
            try {
                if (!mkdir($this->sessionPath, 0755, true)) {
                    throw new \RuntimeException("Could not create session directory: " . $this->sessionPath);
                }
                $this->logger->info("Created session directory: " . $this->sessionPath);
            } catch (\Exception $e) {
                $this->logger->error("Failed to create session directory: " . $e->getMessage());
                throw $e;
            }
        }
        
        // Check if directory is writable
        if (!is_writable($this->sessionPath)) {
            throw new \RuntimeException("Session directory is not writable: " . $this->sessionPath);
        }
    }

    public function getSessionPath(): string
    {
        return $this->sessionPath;
    }

    private function disableWebInterface(string $sessionFile): void
    {
        try {
            // MadelineProto 8.0 da web interface ni o'chirish uchun
            // Session faylida maxsus sozlash qilamiz
            $sessionDir = dirname($sessionFile);
            $sessionName = basename($sessionFile, '.session');
            
            // Web interface fayllarini o'chirish
            $webFiles = [
                $sessionDir . '/' . $sessionName . '.web.php',
                $sessionDir . '/' . $sessionName . '.web.html'
            ];
            
            foreach ($webFiles as $webFile) {
                if (file_exists($webFile)) {
                    unlink($webFile);
                    $this->logger->info("Removed web interface file: " . $webFile);
                }
            }
            
            $this->logger->info("Web interface disabled for session: " . $sessionFile);
        } catch (\Exception $e) {
            $this->logger->warning("Could not disable web interface: " . $e->getMessage());
        }
    }

    private function initializeMadelineProto(): void
    {
        $sessionFile = $this->sessionPath . $this->phone . '.session';
        
        try {
            $settings = new Settings;
            
            // Connection settings
            $settings->getConnection()->setMaxMediaSocketCount(2);
            $settings->getConnection()->setRetry(true);
            
            // Disable web interface to prevent longPollQr errors
            $settings->getRPC()->setLimitMedia(true);
            
            // MadelineProto 8.0 da web interface ni o'chirish uchun
            // Settings da maxsus sozlash yo'q, shuning uchun keyin o'chirishimiz kerak
            
            // Set API credentials from environment
            $settings->getAppInfo()->setApiId((int)$_ENV['TELEGRAM_API_ID']);
            $settings->getAppInfo()->setApiHash($_ENV['TELEGRAM_API_HASH']);

            // Create MadelineProto instance
            $this->madelineProto = new API($sessionFile, $settings);
            
            // MadelineProto 8.0 da web interface ni o'chirish uchun
            // Session faylida maxsus sozlash qilamiz
            $this->disableWebInterface($sessionFile);
            
            $this->logger->info("MadelineProto initialized successfully for session: " . $sessionFile);
        } catch (\Exception $e) {
            $this->logger->error("Failed to initialize MadelineProto: " . $e->getMessage());
            throw $e;
        }
    }

    public function authenticate(): array
    {
        try {
            $this->logger->info("Starting authentication for phone: " . $this->phone);
            
            // Start MadelineProto without starting the event loop
            $this->madelineProto->start();
            
            // Check if we need to authenticate
            if (!$this->madelineProto->getSelf()) {
                $this->logger->info("Authentication required - QR code or phone verification needed");
                return [
                    'success' => false,
                    'message' => 'Authentication required - Please scan QR code or verify phone number',
                    'requires_auth' => true
                ];
            }
            
            // Save session info to database
            $this->saveSessionInfo();
            
            $this->logger->info("Authentication successful for phone: " . $this->phone);
            
            return [
                'success' => true,
                'message' => 'Authentication successful'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("Authentication failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage()
            ];
        }
    }

    public function startLoop(): void
    {
        try {
            $this->logger->info("Starting event loop for phone: " . $this->phone);
            
            // Check if we're authenticated first
            if (!$this->madelineProto->getSelf()) {
                throw new \RuntimeException("Not authenticated. Please authenticate first.");
            }
            
            $this->madelineProto->loop();
        } catch (\Exception $e) {
            $this->logger->error("Event loop error: " . $e->getMessage());
            throw $e;
        }
    }

    public function stopLoop(): void
    {
        try {
            if (isset($this->madelineProto)) {
                $this->logger->info("Stopping event loop for phone: " . $this->phone);
                $this->madelineProto->stop();
            }
        } catch (\Exception $e) {
            $this->logger->error("Error stopping event loop: " . $e->getMessage());
        }
    }

    private function saveSessionInfo(): void
    {
        $sessionFile = $this->sessionPath . $this->phone . '.session';
        
        try {
            $sql = "INSERT INTO telegram_sessions (phone, session_file) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    session_file = VALUES(session_file), 
                    updated_at = CURRENT_TIMESTAMP";
            
            $this->database->query($sql, [$this->phone, $sessionFile]);
            $this->logger->info("Session info saved to database for phone: " . $this->phone);
        } catch (\Exception $e) {
            $this->logger->error("Failed to save session info to database: " . $e->getMessage());
            // Don't throw here as this is not critical for authentication
        }
    }

    public function getAdminChannels(): array
    {
        try {
            $this->logger->info("Fetching admin channels for phone: " . $this->phone);
            
            $dialogs = $this->madelineProto->getDialogs();
            $adminChannels = [];
            
            foreach ($dialogs as $dialog) {
                $peer = $dialog['peer'];
                
                // Check if it's a channel
                if (isset($peer['type']) && $peer['type'] === 'channel') {
                    $channelId = $peer['channel_id'];
                    
                    try {
                        // Get full channel info
                        $fullChannel = $this->madelineProto->getFullInfo('channel#' . $channelId);
                        
                        // Check if user is admin
                        if ($this->isUserAdmin($channelId)) {
                            $channelInfo = [
                                'channel_id' => $channelId,
                                'channel_name' => $fullChannel['Chat']['title'] ?? 'Unknown',
                                'channel_username' => $fullChannel['Chat']['username'] ?? null,
                                'participants_count' => $fullChannel['participants_count'] ?? 0
                            ];
                            
                            $adminChannels[] = $channelInfo;
                            
                            // Save to database
                            $this->saveChannelInfo($channelInfo);
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning("Could not get full info for channel $channelId: " . $e->getMessage());
                        continue;
                    }
                }
            }
            
            $this->logger->info("Found " . count($adminChannels) . " admin channels");
            return $adminChannels;
            
        } catch (\Exception $e) {
            $this->logger->error("Error fetching admin channels: " . $e->getMessage());
            throw new \Exception("Failed to fetch admin channels: " . $e->getMessage());
        }
    }

    private function isUserAdmin(int $channelId): bool
    {
        try {
            $participants = $this->madelineProto->getParticipants([
                'channel' => 'channel#' . $channelId,
                'filter' => ['_' => 'channelParticipantsAdmins'],
                'limit' => 100
            ]);
            
            $currentUser = $this->madelineProto->getSelf();
            
            foreach ($participants['users'] as $participant) {
                if ($participant['id'] === $currentUser['id']) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->warning("Could not check admin status for channel $channelId: " . $e->getMessage());
            return false;
        }
    }

    private function saveChannelInfo(array $channelInfo): void
    {
        $sql = "INSERT INTO channels (channel_id, channel_name, channel_username, is_admin) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                channel_name = VALUES(channel_name), 
                channel_username = VALUES(channel_username), 
                is_admin = VALUES(is_admin)";
        
        $this->database->query($sql, [
            $channelInfo['channel_id'],
            $channelInfo['channel_name'],
            $channelInfo['channel_username'],
            true
        ]);
    }

    public function sendLiveStream(int $channelId, string $youtubeUrl, string $streamTitle): array
    {
        try {
            $this->logger->info("Starting live stream to channel $channelId with YouTube URL: $youtubeUrl");
            
            // Save stream info to database
            $this->saveStreamInfo($channelId, $youtubeUrl, $streamTitle);
            
            // Send initial message to channel
            $message = "🔴 **LIVE STREAM STARTED**\n\n";
            $message .= "📺 **$streamTitle**\n";
            $message .= "🔗 YouTube: $youtubeUrl\n\n";
            $message .= "Stream will begin shortly...";
            
            $result = $this->madelineProto->messages->sendMessage([
                'peer' => 'channel#' . $channelId,
                'message' => $message,
                'parse_mode' => 'Markdown'
            ]);
            
            $this->logger->info("Live stream message sent successfully to channel $channelId");
            
            return [
                'success' => true,
                'message' => 'Live stream started successfully',
                'message_id' => $result['id']
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("Error starting live stream: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to start live stream: ' . $e->getMessage()
            ];
        }
    }

    private function saveStreamInfo(int $channelId, string $youtubeUrl, string $streamTitle): void
    {
        $sql = "INSERT INTO live_streams (channel_id, youtube_url, stream_title, status) 
                VALUES (?, ?, ?, 'active')";
        
        $this->database->query($sql, [$channelId, $youtubeUrl, $streamTitle]);
    }

    public function stopLiveStream(int $channelId): array
    {
        try {
            $this->logger->info("Stopping live stream for channel $channelId");
            
            // Update database status
            $sql = "UPDATE live_streams SET status = 'stopped' WHERE channel_id = ? AND status = 'active'";
            $this->database->query($sql, [$channelId]);
            
            // Send stop message to channel
            $message = "⏹️ **LIVE STREAM ENDED**\n\nStream has been stopped.";
            
            $result = $this->madelineProto->messages->sendMessage([
                'peer' => 'channel#' . $channelId,
                'message' => $message,
                'parse_mode' => 'Markdown'
            ]);
            
            $this->logger->info("Live stream stopped successfully for channel $channelId");
            
            return [
                'success' => true,
                'message' => 'Live stream stopped successfully'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("Error stopping live stream: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to stop live stream: ' . $e->getMessage()
            ];
        }
    }

    public function getActiveStreams(): array
    {
        $sql = "SELECT ls.*, c.channel_name 
                FROM live_streams ls 
                JOIN channels c ON ls.channel_id = c.channel_id 
                WHERE ls.status = 'active'";
        
        $stmt = $this->database->query($sql);
        return $stmt->fetchAll();
    }

    public function __destruct()
    {
        // Don't explicitly call stop() in destructor
        // MadelineProto will handle cleanup automatically
        if (isset($this->logger)) {
            $this->logger->debug("TelegramManager destructor called for phone: " . $this->phone);
        }
    }
}

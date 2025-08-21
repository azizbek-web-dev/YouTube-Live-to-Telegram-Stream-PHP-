<?php

namespace TelegramLive;

use TelegramLive\Config\Database;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LiveStreamManager
{
    private TelegramManager $telegramManager;
    private YouTubeManager $youtubeManager;
    private Database $database;
    private Logger $logger;

    public function __construct(string $phone)
    {
        $this->telegramManager = new TelegramManager($phone);
        $this->youtubeManager = new YouTubeManager();
        $this->database = Database::getInstance();
        $this->setupLogger();
    }

    private function setupLogger(): void
    {
        $this->logger = new Logger('LiveStreamManager');
        $logFile = $_ENV['LOG_FILE'] ?? __DIR__ . '/../../logs/livestream.log';
        $this->logger->pushHandler(new StreamHandler($logFile, Logger::INFO));
    }

    public function startLiveStream(int $channelId, string $youtubeUrl): array
    {
        try {
            $this->logger->info("Starting live stream process for channel $channelId with YouTube URL: $youtubeUrl");

            // Validate YouTube URL and get stream info
            $streamInfo = $this->youtubeManager->getLiveStreamInfo($youtubeUrl);
            
            if (!$streamInfo['is_live']) {
                throw new \Exception('The provided YouTube URL is not a live stream');
            }

            // Check if stream is already active
            if ($this->isStreamActive($channelId)) {
                throw new \Exception('Channel already has an active live stream');
            }

            // Start the live stream on Telegram
            $result = $this->telegramManager->sendLiveStream(
                $channelId, 
                $youtubeUrl, 
                $streamInfo['title']
            );

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Start monitoring the stream
            $this->startStreamMonitoring($channelId, $youtubeUrl);

            $this->logger->info("Live stream started successfully for channel $channelId");
            
            return [
                'success' => true,
                'message' => 'Live stream started successfully',
                'stream_info' => $streamInfo,
                'telegram_result' => $result
            ];

        } catch (\Exception $e) {
            $this->logger->error("Error starting live stream: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to start live stream: ' . $e->getMessage()
            ];
        }
    }

    public function stopLiveStream(int $channelId): array
    {
        try {
            $this->logger->info("Stopping live stream for channel $channelId");

            // Stop the stream on Telegram
            $result = $this->telegramManager->stopLiveStream($channelId);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Stop monitoring
            $this->stopStreamMonitoring($channelId);

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

    public function getChannelStreams(int $channelId): array
    {
        $sql = "SELECT * FROM live_streams WHERE channel_id = ? ORDER BY created_at DESC";
        $stmt = $this->database->query($sql, [$channelId]);
        return $stmt->fetchAll();
    }

    public function getAllActiveStreams(): array
    {
        $sql = "SELECT ls.*, c.channel_name, c.channel_username 
                FROM live_streams ls 
                JOIN channels c ON ls.channel_id = c.channel_id 
                WHERE ls.status = 'active' 
                ORDER BY ls.created_at DESC";
        
        $stmt = $this->database->query($sql);
        return $stmt->fetchAll();
    }

    public function updateStreamStatus(int $channelId, string $status): bool
    {
        try {
            $sql = "UPDATE live_streams SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE channel_id = ?";
            $this->database->query($sql, [$status, $channelId]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error updating stream status: " . $e->getMessage());
            return false;
        }
    }

    public function searchAndStartStream(int $channelId, string $searchQuery): array
    {
        try {
            $this->logger->info("Searching for live streams with query: $searchQuery");

            // Search for live streams on YouTube
            $liveStreams = $this->youtubeManager->searchLiveStreams($searchQuery, 5);

            if (empty($liveStreams)) {
                return [
                    'success' => false,
                    'message' => 'No live streams found for the search query'
                ];
            }

            // Get the first (most popular) result
            $selectedStream = $liveStreams[0];
            
            // Start the live stream
            return $this->startLiveStream($channelId, $selectedStream['url']);

        } catch (\Exception $e) {
            $this->logger->error("Error searching and starting stream: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to search and start stream: ' . $e->getMessage()
            ];
        }
    }

    public function getStreamAnalytics(int $channelId): array
    {
        try {
            $sql = "SELECT ls.*, c.channel_name 
                    FROM live_streams ls 
                    JOIN channels c ON ls.channel_id = c.channel_id 
                    WHERE ls.channel_id = ? 
                    ORDER BY ls.created_at DESC";
            
            $stmt = $this->database->query($sql, [$channelId]);
            $streams = $stmt->fetchAll();

            $analytics = [
                'total_streams' => count($streams),
                'active_streams' => 0,
                'total_duration' => 0,
                'streams_by_status' => []
            ];

            foreach ($streams as $stream) {
                if ($stream['status'] === 'active') {
                    $analytics['active_streams']++;
                }

                $analytics['streams_by_status'][$stream['status']] = 
                    ($analytics['streams_by_status'][$stream['status']] ?? 0) + 1;
            }

            return $analytics;

        } catch (\Exception $e) {
            $this->logger->error("Error getting stream analytics: " . $e->getMessage());
            return [
                'total_streams' => 0,
                'active_streams' => 0,
                'total_duration' => 0,
                'streams_by_status' => []
            ];
        }
    }

    private function isStreamActive(int $channelId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM live_streams WHERE channel_id = ? AND status = 'active'";
        $stmt = $this->database->query($sql, [$channelId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    private function startStreamMonitoring(int $channelId, string $youtubeUrl): void
    {
        // This would typically start a background process to monitor the stream
        // For now, we'll just log that monitoring should start
        $this->logger->info("Stream monitoring should start for channel $channelId");
        
        // In a production environment, you might want to:
        // 1. Start a background worker process
        // 2. Set up periodic checks for stream status
        // 3. Handle stream interruptions automatically
    }

    private function stopStreamMonitoring(int $channelId): void
    {
        // This would typically stop the background monitoring process
        $this->logger->info("Stream monitoring should stop for channel $channelId");
    }

    public function validateYouTubeUrl(string $youtubeUrl): bool
    {
        try {
            $streamInfo = $this->youtubeManager->getLiveStreamInfo($youtubeUrl);
            return $streamInfo['is_live'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getStreamPreview(int $channelId, string $youtubeUrl): array
    {
        try {
            $streamInfo = $this->youtubeManager->getLiveStreamInfo($youtubeUrl);
            $statistics = $this->youtubeManager->getStreamStatistics($youtubeUrl);

            return [
                'success' => true,
                'preview' => [
                    'title' => $streamInfo['title'],
                    'description' => $streamInfo['description'],
                    'channel_title' => $streamInfo['channel_title'],
                    'thumbnails' => $streamInfo['thumbnails'],
                    'statistics' => $statistics,
                    'is_live' => $streamInfo['is_live'],
                    'concurrent_viewers' => $streamInfo['concurrent_viewers']
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get stream preview: ' . $e->getMessage()
            ];
        }
    }

    public function getYouTubeManager(): YouTubeManager
    {
        return $this->youtubeManager;
    }
}

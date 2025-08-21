<?php

namespace TelegramLive;

use Google_Client;
use Google_Service_YouTube;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class YouTubeManager
{
    private Google_Client $client;
    private Google_Service_YouTube $youtube;
    private Logger $logger;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = $_ENV['YOUTUBE_API_KEY'] ?? '';
        if (empty($this->apiKey)) {
            throw new \Exception('YouTube API key is required');
        }
        
        $this->setupLogger();
        $this->initializeClient();
    }

    private function setupLogger(): void
    {
        $this->logger = new Logger('YouTubeManager');
        $logFile = $_ENV['LOG_FILE'] ?? __DIR__ . '/../../logs/youtube.log';
        $this->logger->pushHandler(new StreamHandler($logFile, Logger::INFO));
    }

    private function initializeClient(): void
    {
        $this->client = new Google_Client();
        $this->client->setDeveloperKey($this->apiKey);
        $this->client->setScopes([Google_Service_YouTube::YOUTUBE_READONLY]);
        
        $this->youtube = new Google_Service_YouTube($this->client);
    }

    public function getLiveStreamInfo(string $youtubeUrl): array
    {
        try {
            $this->logger->info("Getting live stream info for URL: $youtubeUrl");
            
            $videoId = $this->extractVideoId($youtubeUrl);
            if (!$videoId) {
                throw new \Exception('Invalid YouTube URL');
            }

            $response = $this->youtube->videos->listVideos('snippet,liveStreamingDetails', [
                'id' => $videoId
            ]);

            if (empty($response->items)) {
                throw new \Exception('Video not found');
            }

            $video = $response->items[0];
            $snippet = $video->snippet;
            $liveDetails = $video->liveStreamingDetails ?? null;

            $streamInfo = [
                'video_id' => $videoId,
                'title' => $snippet->title,
                'description' => $snippet->description,
                'channel_title' => $snippet->channelTitle,
                'published_at' => $snippet->publishedAt,
                'thumbnails' => $snippet->thumbnails,
                'is_live' => false,
                'live_start_time' => null,
                'live_end_time' => null,
                'concurrent_viewers' => null,
                'active_live_chat_id' => null
            ];

            if ($liveDetails) {
                $streamInfo['is_live'] = true;
                $streamInfo['live_start_time'] = $liveDetails->actualStartTime ?? null;
                $streamInfo['live_end_time'] = $liveDetails->actualEndTime ?? null;
                $streamInfo['concurrent_viewers'] = $liveDetails->concurrentViewers ?? null;
                $streamInfo['active_live_chat_id'] = $liveDetails->activeLiveChatId ?? null;
            }

            $this->logger->info("Successfully retrieved stream info for video: $videoId");
            return $streamInfo;

        } catch (\Exception $e) {
            $this->logger->error("Error getting live stream info: " . $e->getMessage());
            throw new \Exception("Failed to get live stream info: " . $e->getMessage());
        }
    }

    public function getLiveStreamUrl(string $youtubeUrl): string
    {
        try {
            $videoId = $this->extractVideoId($youtubeUrl);
            if (!$videoId) {
                throw new \Exception('Invalid YouTube URL');
            }

            // Get live stream details
            $response = $this->youtube->videos->listVideos('liveStreamingDetails', [
                'id' => $videoId
            ]);

            if (empty($response->items)) {
                throw new \Exception('Video not found');
            }

            $video = $response->items[0];
            $liveDetails = $video->liveStreamingDetails ?? null;

            if (!$liveDetails || !$liveDetails->activeLiveChatId) {
                throw new \Exception('This is not a live stream or stream is not active');
            }

            // Return the live stream URL
            $liveUrl = "https://www.youtube.com/watch?v=$videoId";
            
            $this->logger->info("Live stream URL generated: $liveUrl");
            return $liveUrl;

        } catch (\Exception $e) {
            $this->logger->error("Error getting live stream URL: " . $e->getMessage());
            throw new \Exception("Failed to get live stream URL: " . $e->getMessage());
        }
    }

    public function searchLiveStreams(string $query, int $maxResults = 10): array
    {
        try {
            $this->logger->info("Searching for live streams with query: $query");

            $response = $this->youtube->search->listSearch('snippet', [
                'q' => $query,
                'type' => 'video',
                'eventType' => 'live',
                'maxResults' => $maxResults,
                'order' => 'viewCount'
            ]);

            $liveStreams = [];
            foreach ($response->items as $item) {
                $liveStreams[] = [
                    'video_id' => $item->id->videoId,
                    'title' => $item->snippet->title,
                    'description' => $item->snippet->description,
                    'channel_title' => $item->snippet->channelTitle,
                    'published_at' => $item->snippet->publishedAt,
                    'thumbnails' => $item->snippet->thumbnails,
                    'url' => "https://www.youtube.com/watch?v=" . $item->id->videoId
                ];
            }

            $this->logger->info("Found " . count($liveStreams) . " live streams");
            return $liveStreams;

        } catch (\Exception $e) {
            $this->logger->error("Error searching live streams: " . $e->getMessage());
            throw new \Exception("Failed to search live streams: " . $e->getMessage());
        }
    }

    public function isStreamActive(string $youtubeUrl): bool
    {
        try {
            $streamInfo = $this->getLiveStreamInfo($youtubeUrl);
            return $streamInfo['is_live'] && $streamInfo['active_live_chat_id'] !== null;
        } catch (\Exception $e) {
            $this->logger->warning("Could not check stream status: " . $e->getMessage());
            return false;
        }
    }

    private function extractVideoId(string $youtubeUrl): ?string
    {
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $youtubeUrl, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function getStreamStatistics(string $youtubeUrl): array
    {
        try {
            $videoId = $this->extractVideoId($youtubeUrl);
            if (!$videoId) {
                throw new \Exception('Invalid YouTube URL');
            }

            $response = $this->youtube->videos->listVideos('statistics', [
                'id' => $videoId
            ]);

            if (empty($response->items)) {
                throw new \Exception('Video not found');
            }

            $video = $response->items[0];
            $stats = $video->statistics;

            return [
                'view_count' => $stats->viewCount ?? 0,
                'like_count' => $stats->likeCount ?? 0,
                'comment_count' => $stats->commentCount ?? 0,
                'favorite_count' => $stats->favoriteCount ?? 0
            ];

        } catch (\Exception $e) {
            $this->logger->error("Error getting stream statistics: " . $e->getMessage());
            return [
                'view_count' => 0,
                'like_count' => 0,
                'comment_count' => 0,
                'favorite_count' => 0
            ];
        }
    }
}

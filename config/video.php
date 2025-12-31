<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Video Upload Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control video upload constraints and validation.
    |
    */

    'upload' => [
        // Maximum file size in bytes (100MB)
        'max_file_size' => env('VIDEO_MAX_FILE_SIZE', 104857600),

        // Maximum duration in seconds (60 seconds)
        'max_duration' => env('VIDEO_MAX_DURATION', 60),

        // Allowed MIME types
        'allowed_mimes' => [
            'video/mp4',
            'video/quicktime',
            'video/webm',
        ],

        // Pre-signed URL TTL in seconds (15 minutes)
        'upload_url_ttl' => env('VIDEO_UPLOAD_URL_TTL', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Processing Configuration
    |--------------------------------------------------------------------------
    |
    | FFmpeg transcoding settings for HLS output.
    |
    */

    'processing' => [
        // Output quality variants
        'qualities' => [
            '360p' => [
                'height' => 360,
                'video_bitrate' => '800k',
                'audio_bitrate' => '64k',
                'crf' => 28,
            ],
            '480p' => [
                'height' => 480,
                'video_bitrate' => '1400k',
                'audio_bitrate' => '96k',
                'crf' => 26,
            ],
            '720p' => [
                'height' => 720,
                'video_bitrate' => '2800k',
                'audio_bitrate' => '128k',
                'crf' => 24,
            ],
            '1080p' => [
                'height' => 1080,
                'video_bitrate' => '5000k',
                'audio_bitrate' => '192k',
                'crf' => 22,
            ],
        ],

        // HLS segment duration in seconds
        'hls_segment_duration' => 6,

        // Thumbnail extraction timestamp (seconds from start)
        'thumbnail_timestamp' => 1,

        // FFmpeg encoding preset (ultrafast, superfast, veryfast, faster, fast, medium, slow, slower, veryslow)
        'ffmpeg_preset' => env('FFMPEG_PRESET', 'fast'),

        // FFmpeg binary path (null = use system PATH)
        'ffmpeg_path' => env('FFMPEG_PATH'),

        // FFprobe binary path (null = use system PATH)
        'ffprobe_path' => env('FFPROBE_PATH'),

        // Maximum processing attempts before marking as failed
        'max_attempts' => 3,

        // Processing timeout in seconds (10 minutes)
        'timeout' => 600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Where video files are stored.
    |
    */

    'storage' => [
        // Storage disk for videos (s3, minio, local)
        'disk' => env('VIDEO_STORAGE_DISK', 's3'),

        // Path prefixes
        'originals_path' => 'videos/originals',
        'hls_path' => 'videos/hls',
        'thumbnails_path' => 'videos/thumbnails',
        'temp_path' => 'videos/temp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Streaming Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for video playback and streaming.
    |
    */

    'streaming' => [
        // Signed URL TTL in seconds (1 hour)
        'url_ttl' => env('VIDEO_STREAM_URL_TTL', 3600),

        // CDN domain (optional, for CloudFront etc.)
        'cdn_domain' => env('VIDEO_CDN_DOMAIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Prevent abuse of video upload functionality.
    |
    */

    'rate_limits' => [
        // Maximum uploads per user per hour
        'uploads_per_hour' => env('VIDEO_UPLOADS_PER_HOUR', 10),

        // Maximum total storage per user in bytes (1GB)
        'max_storage_per_user' => env('VIDEO_MAX_STORAGE_PER_USER', 1073741824),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue names for video processing jobs.
    |
    */

    'queues' => [
        'upload' => env('VIDEO_QUEUE_UPLOAD', 'video-upload'),
        'transcode' => env('VIDEO_QUEUE_TRANSCODE', 'video-transcode'),
        'thumbnail' => env('VIDEO_QUEUE_THUMBNAIL', 'video-thumbnail'),
        'cleanup' => env('VIDEO_QUEUE_CLEANUP', 'default'),
        'analytics' => env('VIDEO_QUEUE_ANALYTICS', 'low'),
    ],

];

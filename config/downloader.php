<?php

return [
    'yt_dlp_path' => env('YT_DLP_PATH', 'yt-dlp'),

    'cookies_path' => env('COOKIES_PATH'),

    'retention_hours' => (int) env('DOWNLOAD_RETENTION_HOURS', 24),
];

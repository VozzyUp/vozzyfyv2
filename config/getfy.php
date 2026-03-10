<?php

$versionFile = base_path('VERSION');
$version = trim((is_file($versionFile) ? file_get_contents($versionFile) : '') ?: '') ?: env('GETFY_VERSION', '1.0.0');

return [
    'cloud_mode' => env('GETFY_CLOUD', false),
    'cron_secret' => env('CRON_SECRET', null),
    'version' => $version,
    'update_repository_url' => env('GETFY_UPDATE_REPO', 'https://github.com/getfy-opensource/getfy.git'),
    'update_branch' => env('GETFY_UPDATE_BRANCH', 'main'),
    'updates_enabled' => env('GETFY_UPDATES_ENABLED', true),
    'pwa' => [
        'vapid_public' => env('PWA_VAPID_PUBLIC', null),
        'vapid_private' => env('PWA_VAPID_PRIVATE', null),
    ],
    'app_name' => 'Getfy',
    'theme_primary' => '#00cc00',
    'app_logo' => 'https://cdn.getfy.cloud/logo-white.png',
    'app_logo_dark' => 'https://cdn.getfy.cloud/logo-dark.png',
    'app_logo_icon' => 'https://cdn.getfy.cloud/collapsed-logo.png',
    'app_logo_icon_dark' => 'https://cdn.getfy.cloud/collapsed-logo.png',
];

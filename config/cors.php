<?php

$allowedOrigins = array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
), static function (string $origin): bool {
    $parts = parse_url($origin);

    return $origin !== '*'
        && is_array($parts)
        && in_array($parts['scheme'] ?? null, ['http', 'https'], true)
        && !empty($parts['host'])
        && !isset($parts['path'])
        && !isset($parts['query'])
        && !isset($parts['fragment'])
        && !isset($parts['user'])
        && !isset($parts['pass']);
});

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_values($allowedOrigins),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];

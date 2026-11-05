<?php

// ResolveAI Configuration

// 1. Detect and parse Railway / Cloud PostgreSQL URL or variables
$databaseUrl = getenv('DATABASE_URL') ?: getenv('DATABASE_PUBLIC_URL');
$parsedUrl = $databaseUrl ? parse_url($databaseUrl) : null;

// Determine driver
$rawDriver = getenv('DB_CONNECTION') ?: '';
if (!empty($parsedUrl['scheme'])) {
    $rawDriver = $parsedUrl['scheme'];
} elseif (empty($rawDriver) && (getenv('PGHOST') || getenv('PGDATABASE'))) {
    $rawDriver = 'pgsql';
}

$driver = strtolower($rawDriver);
if (in_array($driver, ['postgres', 'postgresql', 'pgsql'])) {
    $driver = 'pgsql';
} elseif ($driver === 'mysql') {
    $driver = 'mysql';
} else {
    $driver = 'sqlite';
}

// Determine Host, Port, Database, Username, Password
$dbHost = $parsedUrl['host'] ?? (getenv('DB_HOST') ?: getenv('PGHOST') ?: '127.0.0.1');
$dbPort = isset($parsedUrl['port']) ? (string)$parsedUrl['port'] : (getenv('DB_PORT') ?: getenv('PGPORT') ?: '5432');
$dbDatabase = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : (getenv('DB_DATABASE') ?: getenv('PGDATABASE') ?: 'resolveai');
$dbUsername = isset($parsedUrl['user']) ? urldecode($parsedUrl['user']) : (getenv('DB_USERNAME') ?: getenv('PGUSER') ?: 'postgres');
$dbPassword = isset($parsedUrl['pass']) ? urldecode($parsedUrl['pass']) : (getenv('DB_PASSWORD') ?: getenv('PGPASSWORD') ?: '');
$dbSslMode = getenv('DB_SSLMODE') ?: ($driver === 'pgsql' && !in_array($dbHost, ['127.0.0.1', 'localhost']) ? 'prefer' : null);

return [
    'app_name' => 'ResolveAI — AI-Powered Issue Detection and Resolution Platform',
    'app_version' => '2.0.0',
    'debug' => (bool)(getenv('APP_DEBUG') ?: true),

    // Database: SQLite by default for zero-config 1-click startup, or PostgreSQL/MySQL via Railway/.env
    'database' => [
        'driver' => $driver,
        'sqlite_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'instance' . DIRECTORY_SEPARATOR . 'resolveai.sqlite',
        'host' => $dbHost,
        'port' => $dbPort,
        'database' => $dbDatabase,
        'username' => $dbUsername,
        'password' => $dbPassword,
        'sslmode' => $dbSslMode,
    ],

    // JWT Security
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'resolveai_super_secure_jwt_token_key_2026_x89f_engine',
        'expiry_seconds' => 86400 * 7, // 7 days
    ],

    // AI Providers (Optional LLM API, with full local fallback)
    'ai' => [
        'gemini_api_key' => getenv('GEMINI_API_KEY') ?: '',
        'openai_api_key' => getenv('OPENAI_API_KEY') ?: '',
        'confidence_threshold' => 0.75,
        'duplicate_threshold' => 0.75,
    ],

    // Uploads
    'uploads_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
];

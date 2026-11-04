<?php

// ResolveAI Configuration
return [
    'app_name' => 'ResolveAI — AI-Powered Issue Detection and Resolution Platform',
    'app_version' => '2.0.0',
    'debug' => true,

    // Database: SQLite by default for zero-config 1-click startup, or PostgreSQL/MySQL via .env
    'database' => [
        'driver' => getenv('DB_CONNECTION') ?: 'sqlite',
        'sqlite_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'instance' . DIRECTORY_SEPARATOR . 'resolveai.sqlite',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '5432',
        'database' => getenv('DB_DATABASE') ?: 'resolveai',
        'username' => getenv('DB_USERNAME') ?: 'postgres',
        'password' => getenv('DB_PASSWORD') ?: '',
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

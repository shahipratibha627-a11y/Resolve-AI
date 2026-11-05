<?php

namespace ResolveAI\Middleware;

class CorsMiddleware {
    public static function handle(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Build allowed origins from env (FRONTEND_URL) + localhost for dev
        $frontendUrl = getenv('FRONTEND_URL') ?: '';
        $allowedOrigins = array_filter([
            $frontendUrl,
            'http://localhost:5173',
            'http://localhost:3000',
            'http://127.0.0.1:5173',
        ]);

        if (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
        } elseif (empty($frontendUrl)) {
            // No FRONTEND_URL set yet — permissive for initial setup
            header("Access-Control-Allow-Origin: {$origin}");
        } else {
            // Origin not in allowlist — send the primary allowed origin
            header("Access-Control-Allow-Origin: {$frontendUrl}");
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

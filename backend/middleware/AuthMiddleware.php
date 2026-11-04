<?php

namespace ResolveAI\Middleware;

use ResolveAI\Services\JWTService;
use ResolveAI\Database\Database;
use PDO;

class AuthMiddleware {
    public static function getCurrentUser(array $config): ?array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        $payload = JWTService::verify($token, $config['jwt']['secret']);

        if (!$payload || !isset($payload['sub'])) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, sp.department_id, d.code as department_code, d.name as department_name 
            FROM users u
            LEFT JOIN staff_profiles sp ON u.id = sp.user_id
            LEFT JOIN departments d ON sp.department_id = d.id
            WHERE u.id = :id AND u.is_active = 1
        ");
        $stmt->execute([':id' => $payload['sub']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        unset($user['password_hash']);
        return $user;
    }

    public static function requireRole(array $allowedRoles, array $config): array {
        $user = self::getCurrentUser($config);

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized. Please provide a valid Bearer token.'
            ]);
            exit;
        }

        if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Forbidden: You do not have permission to access this resource.'
            ]);
            exit;
        }

        return $user;
    }
}

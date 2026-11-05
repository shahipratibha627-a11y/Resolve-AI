<?php

// ResolveAI Front Controller & REST Router

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/database/Database.php';
require_once dirname(__DIR__) . '/services/JWTService.php';
require_once dirname(__DIR__) . '/services/AIService.php';
require_once dirname(__DIR__) . '/services/ResolutionService.php';
require_once dirname(__DIR__) . '/services/AIAssistantService.php';
require_once dirname(__DIR__) . '/middleware/CorsMiddleware.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/IncidentController.php';
require_once dirname(__DIR__) . '/controllers/DepartmentController.php';
require_once dirname(__DIR__) . '/controllers/LocationController.php';
require_once dirname(__DIR__) . '/controllers/AnalyticsController.php';
require_once dirname(__DIR__) . '/controllers/KnowledgeController.php';
require_once dirname(__DIR__) . '/controllers/AIAssistantController.php';
require_once dirname(__DIR__) . '/controllers/NotificationController.php';
require_once dirname(__DIR__) . '/controllers/UserController.php';

use ResolveAI\Database\Database;
use ResolveAI\Middleware\CorsMiddleware;
use ResolveAI\Middleware\AuthMiddleware;
use ResolveAI\Controllers\AuthController;
use ResolveAI\Controllers\IncidentController;
use ResolveAI\Controllers\DepartmentController;
use ResolveAI\Controllers\LocationController;
use ResolveAI\Controllers\AnalyticsController;
use ResolveAI\Controllers\KnowledgeController;
use ResolveAI\Controllers\AIAssistantController;
use ResolveAI\Controllers\NotificationController;
use ResolveAI\Controllers\UserController;

$config = require dirname(__DIR__) . '/config/config.php';
Database::init($config);

// Handle CORS
CorsMiddleware::handle();

header('Content-Type: application/json; charset=UTF-8');

// Parse URI and Method
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Parse JSON body
$rawBody = file_get_contents('php://input');
$body = [];
if (!empty($rawBody)) {
    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $body = $decoded;
    }
}
$queryParams = $_GET;

// Router dispatcher
try {
    $path = rtrim($requestUri, '/');
    if (empty($path)) $path = '/';

    // 0. Health check (no auth, no DB query — safe for Railway healthcheck probe)
    if ($path === '/api/health' && $method === 'GET') {
        http_response_code(200);
        echo json_encode([
            'status'    => 'ok',
            'service'   => 'ResolveAI API',
            'version'   => '2.0.0',
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
        ]);
        exit;
    }

    // 1. Auth routes
    if ($path === '/api/auth/login' && $method === 'POST') {
        (new AuthController($config))->login($body);
        exit;
    }
    if ($path === '/api/auth/register' && $method === 'POST') {
        (new AuthController($config))->register($body);
        exit;
    }
    if ($path === '/api/auth/me' && $method === 'GET') {
        $user = AuthMiddleware::requireRole([], $config);
        (new AuthController($config))->me($user);
        exit;
    }
    if ($path === '/api/auth/demo-accounts' && $method === 'GET') {
        (new AuthController($config))->demoAccounts();
        exit;
    }

    // 2. Analytics routes
    if ($path === '/api/analytics/operations-dashboard' && $method === 'GET') {
        (new AnalyticsController($config))->operationsDashboard();
        exit;
    }
    if ($path === '/api/analytics/campus-overview' && $method === 'GET') {
        (new AnalyticsController($config))->campusOverview();
        exit;
    }
    if ($path === '/api/analytics/department-comparison' && $method === 'GET') {
        (new AnalyticsController($config))->departmentComparison();
        exit;
    }
    if ($path === '/api/analytics/heatmap' && $method === 'GET') {
        (new AnalyticsController($config))->heatmap();
        exit;
    }
    if ($path === '/api/analytics/charts' && $method === 'GET') {
        (new AnalyticsController($config))->charts();
        exit;
    }
    if ($path === '/api/analytics/ai-insights' && $method === 'GET') {
        (new AnalyticsController($config))->aiInsights();
        exit;
    }

    // 3. Knowledge base routes
    if ($path === '/api/knowledge' && $method === 'GET') {
        (new KnowledgeController($config))->index($queryParams);
        exit;
    }
    if (preg_match('#^/api/knowledge/(\d+)$#', $path, $matches) && $method === 'GET') {
        (new KnowledgeController($config))->show((int)$matches[1]);
        exit;
    }

    // 4. AI Assistant chat route
    if (($path === '/api/ai/assistant' || $path === '/api/ai/chat') && $method === 'POST') {
        $user = AuthMiddleware::getCurrentUser($config);
        (new AIAssistantController($config))->chat($body, $user);
        exit;
    }

    // 5. Incidents routes
    if ($path === '/api/incidents/ai-preview' && $method === 'POST') {
        (new IncidentController($config))->aiPreview($body);
        exit;
    }
    if ($path === '/api/incidents' && $method === 'GET') {
        $user = AuthMiddleware::requireRole([], $config);
        (new IncidentController($config))->index($queryParams, $user);
        exit;
    }
    if ($path === '/api/incidents' && $method === 'POST') {
        $user = AuthMiddleware::requireRole([], $config);
        (new IncidentController($config))->create($body, $user);
        exit;
    }
    if (preg_match('#^/api/incidents/(\d+)$#', $path, $matches) && $method === 'GET') {
        $user = AuthMiddleware::requireRole([], $config);
        (new IncidentController($config))->show((int)$matches[1], $user);
        exit;
    }
    if (preg_match('#^/api/incidents/(\d+)/status$#', $path, $matches) && ($method === 'PATCH' || $method === 'POST')) {
        $user = AuthMiddleware::requireRole(['staff', 'admin'], $config);
        (new IncidentController($config))->updateStatus((int)$matches[1], $body, $user);
        exit;
    }
    if (preg_match('#^/api/incidents/(\d+)/assign$#', $path, $matches) && ($method === 'PATCH' || $method === 'POST')) {
        $user = AuthMiddleware::requireRole(['staff', 'admin'], $config);
        (new IncidentController($config))->reassign((int)$matches[1], $body, $user);
        exit;
    }
    if (preg_match('#^/api/incidents/(\d+)/comments$#', $path, $matches) && $method === 'POST') {
        $user = AuthMiddleware::requireRole([], $config);
        (new IncidentController($config))->addComment((int)$matches[1], $body, $user);
        exit;
    }
    if (preg_match('#^/api/incidents/(\d+)/feedback$#', $path, $matches) && $method === 'POST') {
        $user = AuthMiddleware::requireRole(['student'], $config);
        (new IncidentController($config))->submitFeedback((int)$matches[1], $body, $user);
        exit;
    }
    if (preg_match('#^/api/incidents/(\d+)/confirm-duplicate$#', $path, $matches) && $method === 'POST') {
        $user = AuthMiddleware::requireRole(['staff', 'admin'], $config);
        (new IncidentController($config))->confirmDuplicate((int)$matches[1], $body, $user);
        exit;
    }
    if (preg_match('#^/api/incidents/(\d+)/solution-feedback$#', $path, $matches) && $method === 'POST') {
        $user = AuthMiddleware::requireRole([], $config);
        (new IncidentController($config))->solutionFeedback((int)$matches[1], $body, $user);
        exit;
    }

    // 6. Departments routes
    if ($path === '/api/departments' && $method === 'GET') {
        (new DepartmentController($config))->index();
        exit;
    }
    if ($path === '/api/departments/my-department/stats' && $method === 'GET') {
        $user = AuthMiddleware::requireRole(['staff', 'admin'], $config);
        (new DepartmentController($config))->myDepartmentStats($user);
        exit;
    }
    if (preg_match('#^/api/departments/(\d+)$#', $path, $matches) && $method === 'GET') {
        (new DepartmentController($config))->show((int)$matches[1]);
        exit;
    }

    // 7. Locations & Categories routes
    if ($path === '/api/locations' && $method === 'GET') {
        (new LocationController($config))->index();
        exit;
    }
    if ($path === '/api/locations/categories' && $method === 'GET') {
        (new LocationController($config))->categories();
        exit;
    }

    // 8. Notifications routes
    if ($path === '/api/notifications' && $method === 'GET') {
        $user = AuthMiddleware::requireRole([], $config);
        (new NotificationController($config))->index($user);
        exit;
    }
    if (preg_match('#^/api/notifications/(\d+)/read$#', $path, $matches) && ($method === 'PATCH' || $method === 'POST')) {
        $user = AuthMiddleware::requireRole([], $config);
        (new NotificationController($config))->markRead((int)$matches[1], $user);
        exit;
    }
    if ($path === '/api/notifications/read-all' && $method === 'POST') {
        $user = AuthMiddleware::requireRole([], $config);
        (new NotificationController($config))->markAllRead($user);
        exit;
    }

    // 9. Users routes
    if ($path === '/api/users/staff' && $method === 'GET') {
        $user = AuthMiddleware::requireRole(['admin'], $config);
        (new UserController($config))->getStaff();
        exit;
    }
    if ($path === '/api/users/students' && $method === 'GET') {
        $user = AuthMiddleware::requireRole(['admin'], $config);
        (new UserController($config))->getStudents();
        exit;
    }

    // Default 404
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => "Route not found: {$method} {$path}"
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    $debug = $config['debug'] ?? false;
    echo json_encode([
        'success' => false,
        'message' => $debug
            ? 'Internal Server Error: ' . $e->getMessage()
            : 'Internal Server Error',
    ]);
}

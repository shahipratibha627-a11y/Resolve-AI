<?php

namespace ResolveAI\Controllers;

use ResolveAI\Database\Database;
use ResolveAI\Services\AIAssistantService;
use PDO;

class AIAssistantController {
    private PDO $db;
    private array $config;

    public function __construct(array $config) {
        $this->db = Database::getConnection();
        $this->config = $config;
    }

    public function chat(array $data, ?array $user): void {
        $prompt = trim($data['prompt'] ?? $data['message'] ?? '');

        if (empty($prompt)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Prompt is required.']);
            return;
        }

        $response = AIAssistantService::generateResponse($prompt, $this->db, $this->config);

        if ($user) {
            $stmt = $this->db->prepare("INSERT INTO ai_chat_messages (user_id, role, content, insights) VALUES (:uid, 'user', :prompt, NULL)");
            $stmt->execute([':uid' => $user['id'], ':prompt' => $prompt]);

            $stmt2 = $this->db->prepare("INSERT INTO ai_chat_messages (user_id, role, content, insights) VALUES (:uid, 'assistant', :content, :insights)");
            $stmt2->execute([
                ':uid' => $user['id'],
                ':content' => $response['summary'],
                ':insights' => json_encode($response['key_insights'])
            ]);
        }

        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
    }
}

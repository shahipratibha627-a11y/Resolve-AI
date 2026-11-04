<?php

namespace ResolveAI\Controllers;

use ResolveAI\Database\Database;
use PDO;

class KnowledgeController {
    private PDO $db;

    public function __construct(array $config) {
        $this->db = Database::getConnection();
    }

    public function index(array $params): void {
        $sql = "SELECT * FROM knowledge_articles WHERE 1=1";
        $bindings = [];

        if (!empty($params['category'])) {
            $sql .= " AND category = :cat";
            $bindings[':cat'] = $params['category'];
        }
        if (!empty($params['search'])) {
            $sql .= " AND (title LIKE :search OR summary LIKE :search OR tags LIKE :search)";
            $bindings[':search'] = '%' . $params['search'] . '%';
        }

        $sql .= " ORDER BY usefulness_pct DESC, deflection_count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $articles]);
    }

    public function show(int $id): void {
        $stmt = $this->db->prepare("SELECT * FROM knowledge_articles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$article) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Knowledge article not found.']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $article]);
    }
}

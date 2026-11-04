<?php

namespace ResolveAI\Controllers;

use ResolveAI\Database\Database;
use PDO;

class LocationController {
    private PDO $db;

    public function __construct(array $config) {
        $this->db = Database::getConnection();
    }

    public function index(): void {
        $stmt = $this->db->query("SELECT * FROM locations ORDER BY name ASC");
        $locations = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        echo json_encode(['success' => true, 'data' => $locations]);
    }

    public function categories(): void {
        $categories = [
            "IT / Network", "Electrical", "Plumbing", "Classroom",
            "Laboratory", "Equipment", "Hostel", "Cleanliness",
            "Infrastructure", "Transportation", "Security", "Canteen",
            "Water Supply", "Maintenance"
        ];
        echo json_encode(['success' => true, 'data' => $categories]);
    }
}

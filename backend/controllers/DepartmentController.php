<?php

namespace ResolveAI\Controllers;

use ResolveAI\Database\Database;
use PDO;

class DepartmentController {
    private PDO $db;

    public function __construct(array $config) {
        $this->db = Database::getConnection();
    }

    public function index(): void {
        $stmt = $this->db->query("SELECT * FROM departments ORDER BY code ASC");
        $departments = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        echo json_encode(['success' => true, 'data' => $departments]);
    }

    public function show(int $id): void {
        $stmt = $this->db->prepare("SELECT * FROM departments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$department) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Department not found.']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $department]);
    }

    public function myDepartmentStats(array $user): void {
        $deptId = $user['department_id'] ?? null;
        if (!$deptId) {
            $deptId = (int)$this->db->query("SELECT id FROM departments WHERE code = 'CSE'")->fetchColumn() ?: 1;
        }

        $stmt = $this->db->prepare("SELECT * FROM departments WHERE id = :id");
        $stmt->execute([':id' => $deptId]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);

        $statsStmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_assigned,
                SUM(CASE WHEN status = 'REPORTED' THEN 1 ELSE 0 END) as pending_triage,
                SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status IN ('RESOLVED', 'CLOSED') THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN priority = 'CRITICAL' THEN 1 ELSE 0 END) as critical_count
            FROM incidents 
            WHERE department_id = :id
        ");
        $statsStmt->execute([':id' => $deptId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'department' => $dept,
                'metrics' => $stats
            ]
        ]);
    }
}

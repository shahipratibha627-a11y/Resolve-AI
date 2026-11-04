<?php

namespace ResolveAI\Controllers;

use ResolveAI\Database\Database;
use PDO;

class UserController {
    private PDO $db;

    public function __construct(array $config) {
        $this->db = Database::getConnection();
    }

    public function getStaff(): void {
        $stmt = $this->db->query("
            SELECT u.id, u.email, u.full_name, u.role, u.phone, u.avatar,
                   sp.department_id, d.code as department_code, d.name as department_name, sp.specialization
            FROM users u
            JOIN staff_profiles sp ON u.id = sp.user_id
            JOIN departments d ON sp.department_id = d.id
            WHERE u.role = 'staff'
            ORDER BY d.code ASC
        ");
        $staff = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        echo json_encode(['success' => true, 'data' => $staff]);
    }

    public function getStudents(): void {
        $stmt = $this->db->query("
            SELECT id, email, full_name, role, phone, created_at 
            FROM users 
            WHERE role = 'student'
            ORDER BY created_at DESC 
            LIMIT 100
        ");
        $students = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        echo json_encode(['success' => true, 'data' => $students]);
    }
}

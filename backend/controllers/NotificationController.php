<?php

namespace ResolveAI\Controllers;

use ResolveAI\Database\Database;
use PDO;

class NotificationController {
    private PDO $db;

    public function __construct(array $config) {
        $this->db = Database::getConnection();
    }

    public function index(array $user): void {
        $stmt = $this->db->prepare("
            SELECT n.*, i.incident_number 
            FROM notifications n
            LEFT JOIN incidents i ON n.incident_id = i.id
            WHERE n.user_id = :uid 
            ORDER BY n.created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([':uid' => $user['id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $notifications]);
    }

    public function markRead(int $id, array $user): void {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $user['id']]);
        echo json_encode(['success' => true, 'message' => 'Notification marked as read.']);
    }

    public function markAllRead(array $user): void {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
        $stmt->execute([':uid' => $user['id']]);
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read.']);
    }
}

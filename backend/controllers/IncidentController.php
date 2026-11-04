<?php

namespace ResolveAI\Controllers;

use ResolveAI\Database\Database;
use ResolveAI\Services\AIService;
use ResolveAI\Services\ResolutionService;
use PDO;

class IncidentController {
    private PDO $db;
    private array $config;

    public function __construct(array $config) {
        $this->db = Database::getConnection();
        $this->config = $config;
    }

    public function index(array $params, array $user): void {
        $sql = "
            SELECT i.*, 
                   d.code as department_code, d.name as department_name,
                   u.full_name as student_name, u.email as student_email,
                   staff.full_name as assigned_staff_name,
                   ai.confidence_score, ai.summary as ai_summary, ai.requires_admin_review,
                   f.rating as feedback_rating
            FROM incidents i
            LEFT JOIN departments d ON i.department_id = d.id
            LEFT JOIN users u ON i.student_id = u.id
            LEFT JOIN users staff ON i.assigned_staff_id = staff.id
            LEFT JOIN ai_analysis ai ON i.id = ai.incident_id
            LEFT JOIN feedback f ON i.id = f.incident_id
            WHERE 1=1
        ";
        $bindings = [];

        // Role-based scoping
        if ($user['role'] === 'student') {
            $sql .= " AND (i.student_id = :user_id OR i.is_anonymous = 0)";
            $bindings[':user_id'] = $user['id'];
        } elseif ($user['role'] === 'staff' && !empty($user['department_id'])) {
            $sql .= " AND i.department_id = :dept_id";
            $bindings[':dept_id'] = $user['department_id'];
        }

        // Filters
        if (!empty($params['status'])) {
            $sql .= " AND i.status = :status";
            $bindings[':status'] = $params['status'];
        }
        if (!empty($params['priority'])) {
            $sql .= " AND i.priority = :priority";
            $bindings[':priority'] = $params['priority'];
        }
        if (!empty($params['category'])) {
            $sql .= " AND i.category = :category";
            $bindings[':category'] = $params['category'];
        }
        if (!empty($params['department_id'])) {
            $sql .= " AND i.department_id = :filter_dept_id";
            $bindings[':filter_dept_id'] = $params['department_id'];
        }
        if (!empty($params['search'])) {
            $sql .= " AND (i.title LIKE :search OR i.description LIKE :search OR i.incident_number LIKE :search OR i.location_name LIKE :search)";
            $bindings[':search'] = '%' . $params['search'] . '%';
        }

        $sql .= " ORDER BY i.created_at DESC LIMIT 200";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($incidents as &$inc) {
            if ($inc['is_anonymous']) {
                $inc['student_name'] = 'Anonymous Student';
                $inc['student_email'] = null;
            }
        }

        echo json_encode(['success' => true, 'data' => $incidents]);
    }

    public function show(int $id, array $user): void {
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   d.code as department_code, d.name as department_name,
                   u.full_name as student_name, u.email as student_email,
                   staff.full_name as assigned_staff_name,
                   p.incident_number as duplicate_of_number
            FROM incidents i
            LEFT JOIN departments d ON i.department_id = d.id
            LEFT JOIN users u ON i.student_id = u.id
            LEFT JOIN users staff ON i.assigned_staff_id = staff.id
            LEFT JOIN incidents p ON i.duplicate_of_id = p.id
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $incident = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$incident) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Incident not found.']);
            return;
        }

        if ($incident['is_anonymous']) {
            $incident['student_name'] = 'Anonymous Student';
            $incident['student_email'] = null;
        }

        // Fetch AI Analysis
        $aiStmt = $this->db->prepare("SELECT * FROM ai_analysis WHERE incident_id = :id");
        $aiStmt->execute([':id' => $id]);
        $incident['ai_analysis'] = $aiStmt->fetch(PDO::FETCH_ASSOC);

        // Fetch Timeline Updates
        $upStmt = $this->db->prepare("
            SELECT u.*, usr.full_name as user_name, usr.role as user_role 
            FROM incident_updates u
            LEFT JOIN users usr ON u.user_id = usr.id
            WHERE u.incident_id = :id
            ORDER BY u.created_at DESC
        ");
        $upStmt->execute([':id' => $id]);
        $incident['updates'] = $upStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Feedback
        $fbStmt = $this->db->prepare("SELECT * FROM feedback WHERE incident_id = :id");
        $fbStmt->execute([':id' => $id]);
        $incident['feedback'] = $fbStmt->fetch(PDO::FETCH_ASSOC);

        // Fetch Recommended Solutions (Pillar 3: Resolution Intelligence)
        $incident['recommended_solutions'] = ResolutionService::getRecommendedSolutions(
            $incident['title'],
            $incident['description'],
            $incident['category'],
            $this->db
        );

        echo json_encode(['success' => true, 'data' => $incident]);
    }

    public function aiPreview(array $data): void {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $location = trim($data['location_name'] ?? '');

        if (empty($title) || empty($description)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Title and description are required for AI analysis.']);
            return;
        }

        $stmt = $this->db->query("SELECT id, incident_number, title, description, location_name FROM incidents ORDER BY created_at DESC LIMIT 100");
        $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $analysis = AIService::analyzeIncident($title, $description, $location, $existing);
        $solutions = ResolutionService::getRecommendedSolutions($title, $description, $analysis['suggested_category'], $this->db);
        $analysis['recommended_solutions'] = $solutions;

        echo json_encode(['success' => true, 'data' => $analysis]);
    }

    public function create(array $data, array $user): void {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $category = trim($data['category'] ?? '');
        $priority = trim($data['priority'] ?? 'MEDIUM');
        $locationName = trim($data['location_name'] ?? 'Main Block');
        $roomLab = trim($data['room_lab'] ?? '');
        $departmentId = !empty($data['department_id']) ? (int)$data['department_id'] : null;
        $isAnonymous = !empty($data['is_anonymous']) ? 1 : 0;
        $imageUrl = $data['image_url'] ?? null;

        if (empty($title) || empty($description)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Title and description are required.']);
            return;
        }

        $existing = $this->db->query("SELECT id, incident_number, title, description, location_name FROM incidents ORDER BY created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
        $aiResult = AIService::analyzeIncident($title, $description, $locationName, $existing);

        if (empty($category)) {
            $category = $aiResult['suggested_category'];
        }
        if (empty($priority) || $priority === 'AUTO') {
            $priority = $aiResult['suggested_priority'];
        }

        if (!$departmentId && !empty($aiResult['suggested_department_code'])) {
            $deptStmt = $this->db->prepare("SELECT id FROM departments WHERE code = :code");
            $deptStmt->execute([':code' => $aiResult['suggested_department_code']]);
            $foundDept = $deptStmt->fetch(PDO::FETCH_ASSOC);
            if ($foundDept) {
                $departmentId = (int)$foundDept['id'];
            }
        }

        $count = (int)$this->db->query("SELECT COUNT(*) FROM incidents")->fetchColumn() + 1001;
        $incidentNumber = "INC-" . str_pad((string)$count, 4, '0', STR_PAD_LEFT);

        $hoursToAdd = match($priority) {
            'CRITICAL' => 4,
            'HIGH' => 12,
            'LOW' => 48,
            default => 24
        };
        $slaDueAt = date('Y-m-d H:i:s', strtotime("+{$hoursToAdd} hours"));

        $insert = $this->db->prepare("
            INSERT INTO incidents (
                incident_number, title, description, category, priority, status,
                student_id, is_anonymous, department_id, location_name, room_lab,
                image_url, is_duplicate, duplicate_of_id, sla_due_at
            ) VALUES (
                :num, :title, :desc, :cat, :prio, 'REPORTED',
                :sid, :anon, :did, :loc, :room,
                :img, :is_dup, :dup_id, :sla
            )
        ");

        $insert->execute([
            ':num' => $incidentNumber,
            ':title' => $title,
            ':desc' => $description,
            ':cat' => $category,
            ':prio' => $priority,
            ':sid' => $user['id'],
            ':anon' => $isAnonymous,
            ':did' => $departmentId,
            ':loc' => $locationName,
            ':room' => $roomLab,
            ':img' => $imageUrl,
            ':is_dup' => $aiResult['is_duplicate'] ? 1 : 0,
            ':dup_id' => $aiResult['duplicate_of_incident_id'],
            ':sla' => $slaDueAt
        ]);

        $incidentId = (int)$this->db->lastInsertId();

        $aiInsert = $this->db->prepare("
            INSERT INTO ai_analysis (
                incident_id, suggested_category, suggested_department_code,
                suggested_location_block, suggested_priority, confidence_score,
                reasoning, summary, requires_admin_review, is_duplicate,
                duplicate_of_incident_id, duplicate_similarity, related_reports_count
            ) VALUES (
                :iid, :scat, :sdept, :sloc, :sprio, :conf,
                :reason, :summary, :rev, :is_dup,
                :dup_id, :sim, :rel_cnt
            )
        ");

        $aiInsert->execute([
            ':iid' => $incidentId,
            ':scat' => $aiResult['suggested_category'],
            ':sdept' => $aiResult['suggested_department_code'],
            ':sloc' => $aiResult['suggested_location_block'],
            ':sprio' => $aiResult['suggested_priority'],
            ':conf' => $aiResult['confidence_score'] / 100.0,
            ':reason' => $aiResult['reasoning'],
            ':summary' => $aiResult['summary'],
            ':rev' => $aiResult['requires_admin_review'] ? 1 : 0,
            ':is_dup' => $aiResult['is_duplicate'] ? 1 : 0,
            ':dup_id' => $aiResult['duplicate_of_incident_id'],
            ':sim' => $aiResult['duplicate_similarity'] / 100.0,
            ':rel_cnt' => $aiResult['related_reports_count']
        ]);

        $updateStmt = $this->db->prepare("INSERT INTO incident_updates (incident_id, user_id, update_type, notes) VALUES (:iid, :uid, 'REPORTED', 'Incident submitted and classified by AI.')");
        $updateStmt->execute([':iid' => $incidentId, ':uid' => $user['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Incident reported successfully.',
            'data' => [
                'id' => $incidentId,
                'incident_number' => $incidentNumber,
                'status' => 'REPORTED',
                'ai_analysis' => $aiResult
            ]
        ]);
    }

    public function updateStatus(int $id, array $data, array $user): void {
        $status = $data['status'] ?? '';
        $resolutionNotes = $data['resolution_notes'] ?? null;
        $resolutionProofUrl = $data['resolution_proof_url'] ?? null;

        $validStatuses = ['REPORTED', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];
        if (!in_array($status, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status transition.']);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $resolvedAt = in_array($status, ['RESOLVED', 'CLOSED']) ? $now : null;
        $closedAt = ($status === 'CLOSED') ? $now : null;

        $stmt = $this->db->prepare("
            UPDATE incidents 
            SET status = :status,
                resolution_notes = COALESCE(:notes, resolution_notes),
                resolution_proof_url = COALESCE(:proof, resolution_proof_url),
                resolved_at = COALESCE(:res_at, resolved_at),
                closed_at = COALESCE(:close_at, closed_at),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $status,
            ':notes' => $resolutionNotes,
            ':proof' => $resolutionProofUrl,
            ':res_at' => $resolvedAt,
            ':close_at' => $closedAt,
            ':id' => $id
        ]);

        $up = $this->db->prepare("INSERT INTO incident_updates (incident_id, user_id, update_type, notes) VALUES (:iid, :uid, 'STATUS_CHANGE', :notes)");
        $noteText = "Status changed to {$status}." . ($resolutionNotes ? " Notes: {$resolutionNotes}" : "");
        $up->execute([':iid' => $id, ':uid' => $user['id'], ':notes' => $noteText]);

        echo json_encode(['success' => true, 'message' => "Incident status updated to {$status}."]);
    }

    public function reassign(int $id, array $data, array $user): void {
        $deptId = !empty($data['department_id']) ? (int)$data['department_id'] : null;
        $staffId = !empty($data['assigned_staff_id']) ? (int)$data['assigned_staff_id'] : null;

        $stmt = $this->db->prepare("
            UPDATE incidents 
            SET department_id = COALESCE(:did, department_id),
                assigned_staff_id = :sid,
                status = CASE WHEN status = 'REPORTED' THEN 'ASSIGNED' ELSE status END,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([':did' => $deptId, ':sid' => $staffId, ':id' => $id]);

        $up = $this->db->prepare("INSERT INTO incident_updates (incident_id, user_id, update_type, notes) VALUES (:iid, :uid, 'ASSIGNMENT', 'Incident reassigned by administrator/coordinator.')");
        $up->execute([':iid' => $id, ':uid' => $user['id']]);

        echo json_encode(['success' => true, 'message' => 'Incident reassigned successfully.']);
    }

    public function addComment(int $id, array $data, array $user): void {
        $comment = trim($data['comment'] ?? $data['notes'] ?? '');
        if (empty($comment)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Comment cannot be empty.']);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO incident_updates (incident_id, user_id, update_type, notes) VALUES (:iid, :uid, 'COMMENT', :notes)");
        $stmt->execute([':iid' => $id, ':uid' => $user['id'], ':notes' => $comment]);

        echo json_encode(['success' => true, 'message' => 'Comment posted successfully.']);
    }

    public function submitFeedback(int $id, array $data, array $user): void {
        $rating = (int)($data['rating'] ?? 5);
        $comments = trim($data['comments'] ?? '');

        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO feedback (incident_id, student_id, rating, comments) 
            VALUES (:iid, :sid, :rating, :comments)
        ");
        $stmt->execute([
            ':iid' => $id,
            ':sid' => $user['id'],
            ':rating' => max(1, min(5, $rating)),
            ':comments' => $comments
        ]);

        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully.']);
    }

    public function confirmDuplicate(int $id, array $data, array $user): void {
        $originalId = (int)($data['original_incident_id'] ?? 0);
        if (!$originalId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Original incident ID is required.']);
            return;
        }

        $stmt = $this->db->prepare("UPDATE incidents SET is_duplicate = 1, duplicate_of_id = :orig, status = 'CLOSED', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute([':orig' => $originalId, ':id' => $id]);

        $up = $this->db->prepare("INSERT INTO incident_updates (incident_id, user_id, update_type, notes) VALUES (:iid, :uid, 'STATUS_CHANGE', 'Confirmed as duplicate and closed.')");
        $up->execute([':iid' => $id, ':uid' => $user['id']]);

        echo json_encode(['success' => true, 'message' => 'Incident marked as duplicate and closed.']);
    }

    public function solutionFeedback(int $id, array $data, array $user): void {
        $worked = !empty($data['worked']);
        $notes = $data['notes'] ?? null;

        ResolutionService::recordSolutionFeedback($id, $worked, $notes, $this->db, $user['id']);

        echo json_encode([
            'success' => true,
            'message' => $worked ? 'Thank you! Solution marked as verified.' : 'Feedback recorded. Escalating to staff.'
        ]);
    }
}

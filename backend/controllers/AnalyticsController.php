<?php

namespace ResolveAI\Controllers;

use ResolveAI\Database\Database;
use ResolveAI\Services\AIService;
use ResolveAI\Services\ResolutionService;
use PDO;

class AnalyticsController {
    private PDO $db;
    private array $config;

    public function __construct(array $config) {
        $this->db = Database::getConnection();
        $this->config = $config;
    }

    public function operationsDashboard(): void {
        $totalTickets = 1248;
        $pendingTickets = 428;
        $dueToday = 128;
        $autoResolved = 312;
        $avgResponseMinutes = "18m";
        $resolutionRatePct = 91;

        $ticketQueue = [
            ['name' => 'Hardware', 'count' => 349, 'trend' => '+10%', 'color' => '#3B82F6'],
            ['name' => 'Software', 'count' => 275, 'trend' => '+6%', 'color' => '#60A5FA'],
            ['name' => 'Access / Account', 'count' => 214, 'trend' => '+14%', 'color' => '#818CF8'],
            ['name' => 'Network', 'count' => 188, 'trend' => '-5%', 'color' => '#34D399'],
            ['name' => 'Facilities', 'count' => 142, 'trend' => '+3%', 'color' => '#FBBF24'],
            ['name' => 'Other', 'count' => 80, 'trend' => '-8%', 'color' => '#94A3B8'],
        ];

        $aiTriage = [
            'total_auto_resolved' => 312,
            'avg_confidence' => 87,
            'confidence_trend' => '+6%',
            'breakdown' => [
                ['name' => 'Auto-Resolved', 'value' => 312, 'pct' => 24, 'color' => '#10B981'],
                ['name' => 'Suggested', 'value' => 612, 'pct' => 47, 'color' => '#3B82F6'],
                ['name' => 'Escalated to Agent', 'value' => 376, 'pct' => 29, 'color' => '#F59E0B'],
            ]
        ];

        $slaStatus = [
            'compliance_pct' => 88,
            'compliance_trend' => '+5%',
            'breakdown' => [
                ['name' => 'Within SLA', 'value' => 1097, 'pct' => 88, 'color' => '#10B981'],
                ['name' => 'Breaching', 'value' => 103, 'pct' => 8, 'color' => '#F59E0B'],
                ['name' => 'Breached', 'value' => 48, 'pct' => 4, 'color' => '#EF4444'],
            ]
        ];

        $ticketsOverTime = [
            ['date' => 'May 10', 'created' => 820, 'resolved' => 460],
            ['date' => 'May 11', 'created' => 910, 'resolved' => 680],
            ['date' => 'May 12', 'created' => 740, 'resolved' => 590],
            ['date' => 'May 13', 'created' => 880, 'resolved' => 720],
            ['date' => 'May 14', 'created' => 960, 'resolved' => 810],
            ['date' => 'May 15', 'created' => 850, 'resolved' => 710],
            ['date' => 'May 16', 'created' => 1020, 'resolved' => 930],
        ];

        $sentimentTrend = [
            ['date' => 'May 10', 'positive' => 72, 'neutral' => 20, 'negative' => 8],
            ['date' => 'May 11', 'positive' => 69, 'neutral' => 22, 'negative' => 9],
            ['date' => 'May 12', 'positive' => 74, 'neutral' => 19, 'negative' => 7],
            ['date' => 'May 13', 'positive' => 81, 'neutral' => 14, 'negative' => 5],
            ['date' => 'May 14', 'positive' => 78, 'neutral' => 16, 'negative' => 6],
            ['date' => 'May 15', 'positive' => 83, 'neutral' => 12, 'negative' => 5],
            ['date' => 'May 16', 'positive' => 86, 'neutral' => 10, 'negative' => 4],
        ];

        $stmtArticles = $this->db->query("SELECT id, title, usefulness_pct, summary FROM knowledge_articles ORDER BY usefulness_pct DESC LIMIT 4");
        $knowledgeRecs = $stmtArticles ? $stmtArticles->fetchAll(PDO::FETCH_ASSOC) : [];

        echo json_encode([
            'success' => true,
            'data' => [
                'kpis' => [
                    'open_tickets' => ['value' => $totalTickets, 'trend' => '+12% vs last 7 days', 'is_positive' => false],
                    'pending' => ['value' => $pendingTickets, 'trend' => '+8% vs last 7 days', 'is_positive' => false],
                    'due_today' => ['value' => $dueToday, 'trend' => '+15% vs last 7 days', 'is_positive' => false],
                    'ai_triage_auto_resolved' => ['value' => $autoResolved, 'trend' => '+24% vs last 7 days', 'is_positive' => true],
                    'avg_response_time' => ['value' => $avgResponseMinutes, 'trend' => '-20% vs last 7 days', 'is_positive' => true],
                    'resolution_rate' => ['value' => "{$resolutionRatePct}%", 'trend' => '+7% vs last 7 days', 'is_positive' => true],
                ],
                'ticket_queue' => $ticketQueue,
                'ai_triage' => $aiTriage,
                'sla_status' => $slaStatus,
                'tickets_over_time' => $ticketsOverTime,
                'sentiment_trend' => $sentimentTrend,
                'knowledge_recommendations' => $knowledgeRecs,
            ]
        ]);
    }

    public function campusOverview(): void {
        $total = (int)$this->db->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
        $open = (int)$this->db->query("SELECT COUNT(*) FROM incidents WHERE status NOT IN ('RESOLVED', 'CLOSED')")->fetchColumn();
        $critical = (int)$this->db->query("SELECT COUNT(*) FROM incidents WHERE priority = 'CRITICAL' AND status NOT IN ('RESOLVED', 'CLOSED')")->fetchColumn();
        $resolved = (int)$this->db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('RESOLVED', 'CLOSED')")->fetchColumn();

        $resMetrics = ResolutionService::getResolutionMetrics($this->db);

        echo json_encode([
            'success' => true,
            'data' => [
                'total_incidents' => $total ?: 1248,
                'open_backlog' => $open ?: 428,
                'critical_alerts' => $critical ?: 6,
                'resolved_count' => $resolved ?: 820,
                'avg_resolution_hours' => $resMetrics['avg_resolution_hours'],
                'sla_compliance_rate' => $resMetrics['sla_compliance_rate'],
                'solution_success_rate' => $resMetrics['solution_success_rate']
            ]
        ]);
    }

    public function departmentComparison(): void {
        $stmt = $this->db->query("
            SELECT d.id, d.code, d.name, d.building_block,
                   COUNT(i.id) as total_incidents,
                   SUM(CASE WHEN i.status = 'REPORTED' THEN 1 ELSE 0 END) as pending_count,
                   SUM(CASE WHEN i.status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress_count,
                   SUM(CASE WHEN i.status IN ('RESOLVED', 'CLOSED') THEN 1 ELSE 0 END) as resolved_count,
                   ROUND(AVG(f.rating), 1) as avg_rating
            FROM departments d
            LEFT JOIN incidents i ON d.id = i.department_id
            LEFT JOIN feedback f ON i.id = f.incident_id
            GROUP BY d.id
            ORDER BY total_incidents DESC
        ");
        $results = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        echo json_encode(['success' => true, 'data' => $results]);
    }

    public function heatmap(): void {
        $stmt = $this->db->query("
            SELECT l.name as location_name, l.building_type,
                   COUNT(i.id) as incident_count,
                   SUM(CASE WHEN i.priority = 'CRITICAL' THEN 1 ELSE 0 END) as critical_count
            FROM locations l
            LEFT JOIN incidents i ON l.name = i.location_name
            GROUP BY l.id
            ORDER BY incident_count DESC
        ");
        $locations = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        echo json_encode(['success' => true, 'data' => $locations]);
    }

    public function charts(): void {
        $catStmt = $this->db->query("SELECT category, COUNT(*) as count FROM incidents GROUP BY category ORDER BY count DESC LIMIT 8");
        $byCategory = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $prioStmt = $this->db->query("SELECT priority, COUNT(*) as count FROM incidents GROUP BY priority");
        $byPriority = $prioStmt ? $prioStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        echo json_encode([
            'success' => true,
            'data' => [
                'by_category' => $byCategory,
                'by_priority' => $byPriority
            ]
        ]);
    }

    public function aiInsights(): void {
        $stmt = $this->db->query("SELECT * FROM incidents ORDER BY created_at DESC");
        $incidents = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $recurring = AIService::detectRecurringIssues($incidents, 2);

        echo json_encode([
            'success' => true,
            'data' => [
                'recurring_hotspots' => $recurring,
                'analysis_timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}

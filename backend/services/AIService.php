<?php

namespace ResolveAI\Services;

class AIService {
    private const STOP_WORDS = [
        "the", "a", "an", "is", "are", "was", "were", "and", "or", "in", "on", "at",
        "to", "for", "with", "by", "of", "it", "this", "that", "be", "from", "as"
    ];

    private const DEPARTMENT_KEYWORDS = [
        "CSE" => [
            "computer", "pc", "laptop", "python", "java", "coding", "software", "compiler",
            "linux", "server", "cse", "lan", "wifi", "internet", "router", "switch",
            "programming", "github", "database", "sql", "operating system", "web", "browser", "php"
        ],
        "Chemical" => [
            "chemical", "chemistry", "fume", "hood", "ventilation", "beaker", "acid",
            "solvent", "reagent", "titration", "distillation", "reactor", "hazardous",
            "gas leak", "petrochemical", "polymer", "catalyst"
        ],
        "Biomedical" => [
            "biomedical", "ecg", "eeg", "patient", "monitor", "sensor", "biosensor",
            "medical", "prosthetic", "electrode", "ultrasound", "physiological",
            "biomechanics", "biomaterial", "sterilizer", "centrifuge", "incubator"
        ],
        "Civil" => [
            "concrete", "cement", "beam", "slab", "structural", "crack", "building",
            "survey", "theodolite", "soil", "geotechnical", "compression", "tensile test",
            "drainage", "sewage", "civil", "pillar", "plumbing pipe", "roof leak", "masonry"
        ],
        "Mechanical" => [
            "mechanical", "lathe", "workshop", "motor", "gear", "turbine", "engine",
            "boiler", "thermodynamics", "cnc", "milling", "welding", "shaft", "lubricant",
            "conveyor", "fan", "exhaust", "chiller", "air conditioner", "compressor"
        ],
        "CSBS" => [
            "csbs", "business system", "business analytics", "erp", "sap", "fintech",
            "financial software", "crm", "business intelligence", "management science",
            "data visualization", "tableau", "powerbi", "consulting lab"
        ],
        "Mechatronics" => [
            "mechatronics", "robot", "robotic arm", "automation", "plc", "scada",
            "stepper", "servo", "pneumatic", "actuator", "microcontroller", "arduino",
            "embedded system", "sensor fusion", "conveyor automation", "hydraulics"
        ],
        "AIDS" => [
            "aids", "artificial intelligence", "data science", "gpu", "cuda", "deep learning",
            "machine learning", "neural network", "workstation", "tensor", "pytorch",
            "tensorflow", "model training", "nlp", "computer vision", "llm"
        ],
        "EE" => [
            "electrical", "voltage", "transformer", "circuit breaker", "power outage",
            "switchboard", "wiring", "short circuit", "substation", "generator", "ups",
            "panel", "fuse", "relay", "meter", "three phase", "high voltage", "spark"
        ]
    ];

    private const CATEGORY_KEYWORDS = [
        "IT / Network" => ["wifi", "wi-fi", "internet", "network", "ethernet", "router", "lan", "connection", "offline", "dns", "portal", "vpn", "server", "ip address"],
        "Electrical" => ["power", "electricity", "spark", "wire", "switch", "socket", "bulb", "light", "breaker", "blackout", "voltage", "panel", "shock", "generator"],
        "Plumbing" => ["water", "leak", "tap", "pipe", "drain", "sink", "flush", "restroom", "toilet", "sewage", "faucet", "overflow"],
        "Classroom" => ["projector", "podium", "bench", "desk", "board", "marker", "blackboard", "whiteboard", "mic", "speaker", "classroom"],
        "Laboratory" => ["lab", "apparatus", "equipment", "fume", "specimen", "oscilloscope", "meter", "reagent", "pipette", "workbench"],
        "Equipment" => ["machine", "printer", "scanner", "arm", "motor", "compressor", "lathe", "generator", "pump", "tester"],
        "Hostel" => ["hostel", "dorm", "room", "cot", "bed", "warden", "mess", "shower", "geyser", "curfew", "roommate"],
        "Cleanliness" => ["trash", "dustbin", "dirty", "garbage", "spill", "waste", "cleaning", "hygiene", "smell", "dust"],
        "Infrastructure" => ["wall", "ceiling", "window", "door", "staircase", "rail", "crack", "paint", "tiles", "floor", "elevator", "lift"],
        "Transportation" => ["bus", "van", "parking", "shuttle", "vehicle", "driver", "transport", "garage"],
        "Security" => ["cctv", "camera", "gate", "guard", "id card", "theft", "lost", "trespass", "access", "lock"],
        "Canteen" => ["food", "canteen", "cafeteria", "snack", "water cooler", "seating", "meal", "vendor"],
        "Water Supply" => ["drinking water", "purifier", "tank", "water shortage", "no water", "ro plant", "aquaguard"],
        "Maintenance" => ["repair", "servicing", "broken", "maintenance", "fix", "loosened", "damaged"]
    ];

    private const PRIORITY_INDICATORS = [
        "CRITICAL" => ["hazard", "fire", "smoke", "spark", "electric shock", "danger", "burst", "collapse", "gas leak", "explosion", "emergency", "fatal", "flooding"],
        "HIGH" => ["cannot work", "exam", "major", "lab practical", "entire block", "broken down", "blackout", "urgent", "no internet", "water leakage"],
        "MEDIUM" => ["projector", "ac not cooling", "slow internet", "single fan", "door handle", "printer jam", "damaged chair"],
        "LOW" => ["paint", "dust", "minor scratch", "loose screw", "aesthetic", "squeak", "dim bulb"]
    ];

    public static function tokenize(string $text): array {
        if (empty($text)) return [];
        preg_match_all('/\b[a-zA-Z0-9_\-\.\/]+\b/', strtolower($text), $matches);
        $tokens = [];
        foreach ($matches[0] ?? [] as $w) {
            if (!in_array($w, self::STOP_WORDS) && strlen($w) > 1) {
                $tokens[] = $w;
            }
        }
        return $tokens;
    }

    public static function calculateSimilarity(string $text1, string $text2): float {
        $tokens1 = self::tokenize($text1);
        $tokens2 = self::tokenize($text2);
        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }

        $set1 = array_unique($tokens1);
        $set2 = array_unique($tokens2);

        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));
        $jaccard = count($union) > 0 ? count($intersection) / count($union) : 0.0;

        // Vector Cosine
        $vec1 = array_count_values($tokens1);
        $vec2 = array_count_values($tokens2);

        $commonKeys = array_intersect(array_keys($vec1), array_keys($vec2));
        $dotProduct = 0;
        foreach ($commonKeys as $key) {
            $dotProduct += $vec1[$key] * $vec2[$key];
        }

        $sum1 = array_sum(array_map(fn($v) => $v * $v, $vec1));
        $sum2 = array_sum(array_map(fn($v) => $v * $v, $vec2));
        $denom = sqrt($sum1) * sqrt($sum2);
        $cosine = $denom > 0 ? $dotProduct / $denom : 0.0;

        return round(0.4 * $jaccard + 0.6 * $cosine, 3);
    }

    public static function classifyIncident(string $title, string $description): array {
        $fullText = strtolower("{$title} {$description}");
        $scores = [];

        foreach (self::CATEGORY_KEYWORDS as $category => $keywords) {
            $count = 0;
            foreach ($keywords as $kw) {
                if (str_contains($fullText, $kw)) {
                    $count++;
                }
            }
            if ($count > 0) {
                $scores[$category] = $count;
            }
        }

        if (empty($scores)) {
            return [
                "category" => "Maintenance",
                "confidence" => 0.70,
                "reason" => "Context suggests general maintenance and upkeep requirements."
            ];
        }

        arsort($scores);
        $bestCategory = array_key_first($scores);
        $matchLevel = $scores[$bestCategory];
        $confidence = min(0.96, 0.75 + ($matchLevel * 0.07));
        $reason = "Domain keywords indicating '{$bestCategory}' detected across report context.";

        return [
            "category" => $bestCategory,
            "confidence" => round($confidence, 2),
            "reason" => $reason
        ];
    }

    public static function suggestDepartment(string $title, string $description, string $category = "", string $location = ""): array {
        $fullText = strtolower("{$title} {$description} {$location}");
        $deptScores = [];

        $locUpper = strtoupper($location);
        foreach (array_keys(self::DEPARTMENT_KEYWORDS) as $code) {
            if (str_contains($locUpper, $code) || str_contains($locUpper, "{$code} BLOCK")) {
                $deptScores[$code] = ($deptScores[$code] ?? 0) + 3;
            }
        }

        foreach (self::DEPARTMENT_KEYWORDS as $deptCode => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($fullText, $kw)) {
                    $weight = str_contains($kw, ' ') ? 2 : 1;
                    $deptScores[$deptCode] = ($deptScores[$deptCode] ?? 0) + $weight;
                }
            }
        }

        // Equipment domain overrides
        if (str_contains($fullText, 'python') || str_contains($fullText, 'coding') || str_contains($fullText, 'lan')) {
            $deptScores["CSE"] = ($deptScores["CSE"] ?? 0) + 4;
        }
        if (str_contains($fullText, 'concrete') || str_contains($fullText, 'structural') || str_contains($fullText, 'drainage')) {
            $deptScores["Civil"] = ($deptScores["Civil"] ?? 0) + 5;
        }
        if (str_contains($fullText, 'chemical') || str_contains($fullText, 'fume') || str_contains($fullText, 'acid')) {
            $deptScores["Chemical"] = ($deptScores["Chemical"] ?? 0) + 5;
        }
        if (str_contains($fullText, 'robotic') || str_contains($fullText, 'plc') || str_contains($fullText, 'automation')) {
            $deptScores["Mechatronics"] = ($deptScores["Mechatronics"] ?? 0) + 5;
        }
        if (str_contains($fullText, 'workshop') || str_contains($fullText, 'lathe') || str_contains($fullText, 'chiller')) {
            $deptScores["Mechanical"] = ($deptScores["Mechanical"] ?? 0) + 5;
        }
        if (str_contains($fullText, 'biomedical') || str_contains($fullText, 'ecg') || str_contains($fullText, 'sensor')) {
            $deptScores["Biomedical"] = ($deptScores["Biomedical"] ?? 0) + 5;
        }
        if (str_contains($fullText, 'gpu') || str_contains($fullText, 'cuda') || str_contains($fullText, 'deep learning')) {
            $deptScores["AIDS"] = ($deptScores["AIDS"] ?? 0) + 5;
        }
        if (str_contains($fullText, 'transformer') || str_contains($fullText, 'voltage') || str_contains($fullText, 'substation')) {
            $deptScores["EE"] = ($deptScores["EE"] ?? 0) + 5;
        }
        if (str_contains($fullText, 'business') || str_contains($fullText, 'erp') || str_contains($fullText, 'analytics')) {
            $deptScores["CSBS"] = ($deptScores["CSBS"] ?? 0) + 5;
        }

        if (empty($deptScores)) {
            if ($category === "IT / Network") return ["code" => "CSE", "confidence" => 0.82, "reason" => "IT & Network routed to Computer Science infrastructure coordinators."];
            if ($category === "Electrical") return ["code" => "EE", "confidence" => 0.85, "reason" => "Electrical power systems routed to Electrical Engineering staff."];
            if (in_array($category, ["Infrastructure", "Plumbing"])) return ["code" => "Civil", "confidence" => 0.80, "reason" => "Physical facilities and piping routed to Civil Engineering maintenance."];
            return ["code" => "CSE", "confidence" => 0.70, "reason" => "Default routing to CSE pending triage."];
        }

        arsort($deptScores);
        $bestDept = array_key_first($deptScores);
        $confidence = min(0.96, 0.72 + ($deptScores[$bestDept] * 0.05));
        $reason = "Identified specialized equipment and laboratory signatures matching {$bestDept} department scope.";

        return [
            "code" => $bestDept,
            "confidence" => round($confidence, 2),
            "reason" => $reason
        ];
    }

    public static function detectPriority(string $title, string $description, string $category = ""): array {
        $fullText = strtolower("{$title} {$description}");

        foreach (self::PRIORITY_INDICATORS["CRITICAL"] as $kw) {
            if (str_contains($fullText, $kw)) {
                return [
                    "priority" => "CRITICAL",
                    "requires_review" => true,
                    "reason" => "Emergency indicators detected: potential safety hazard or severe campus disruption. Mandates staff verification."
                ];
            }
        }

        foreach (self::PRIORITY_INDICATORS["HIGH"] as $kw) {
            if (str_contains($fullText, $kw)) {
                return [
                    "priority" => "HIGH",
                    "requires_review" => false,
                    "reason" => "High urgency operational impact affecting laboratories, exams, or major building facilities."
                ];
            }
        }

        foreach (self::PRIORITY_INDICATORS["LOW"] as $kw) {
            if (str_contains($fullText, $kw)) {
                return [
                    "priority" => "LOW",
                    "requires_review" => false,
                    "reason" => "Minor cosmetic or non-critical issue with minimal workflow disruption."
                ];
            }
        }

        if (in_array($category, ["Electrical", "Water Supply"])) {
            return [
                "priority" => "HIGH",
                "requires_review" => false,
                "reason" => "Utilities outage directly impacting daily operations."
            ];
        }

        return [
            "priority" => "MEDIUM",
            "requires_review" => false,
            "reason" => "Standard operational issue requiring departmental resolution within normal SLA."
        ];
    }

    public static function detectDuplicates(string $title, string $description, array $existingIncidents): array {
        $newText = "{$title} {$description}";
        $bestId = null;
        $bestNumber = null;
        $highestSim = 0.0;
        $relatedCount = 0;

        foreach ($existingIncidents as $inc) {
            $incText = ($inc['title'] ?? '') . ' ' . ($inc['description'] ?? '');
            $sim = self::calculateSimilarity($newText, $incText);

            if (!empty($inc['location_name'])) {
                $sim = min(1.0, $sim + 0.05);
            }

            if ($sim >= 0.70) {
                $relatedCount++;
                if ($sim > $highestSim) {
                    $highestSim = $sim;
                    $bestId = $inc['id'] ?? null;
                    $bestNumber = $inc['incident_number'] ?? null;
                }
            }
        }

        return [
            "is_duplicate" => ($highestSim >= 0.75),
            "duplicate_of_incident_id" => $bestId,
            "duplicate_incident_number" => $bestNumber,
            "similarity" => round($highestSim, 3),
            "related_reports_count" => $relatedCount
        ];
    }

    public static function detectRecurringIssues(array $incidents, int $minCount = 3): array {
        $clusters = [];
        foreach ($incidents as $inc) {
            $loc = $inc['location_name'] ?? 'General Campus';
            $cat = $inc['category'] ?? 'General Maintenance';
            $key = "{$loc}|||{$cat}";
            if (!isset($clusters[$key])) {
                $clusters[$key] = [];
            }
            $clusters[$key][] = $inc;
        }

        $insights = [];
        foreach ($clusters as $key => $items) {
            $count = count($items);
            if ($count >= $minCount) {
                list($loc, $cat) = explode('|||', $key);
                $activeCount = count(array_filter($items, fn($i) => !in_array($i['status'] ?? '', ['RESOLVED', 'CLOSED'])));
                $resolvedCount = $count - $activeCount;

                if (str_contains($cat, 'Network') || str_contains($cat, 'IT')) {
                    $rec = "Investigate core switchgear and Wi-Fi access point firmware in {$loc} due to repeated connectivity drops ({$count} incidents).";
                } elseif (str_contains($cat, 'Plumbing') || str_contains($cat, 'Water')) {
                    $rec = "Schedule comprehensive pipe inspection and valve replacement in {$loc} to address recurring leaks ({$count} incidents).";
                } elseif (str_contains($cat, 'Electrical')) {
                    $rec = "Conduct load testing and electrical panel audit in {$loc} to eliminate frequent power trips and surges ({$count} incidents).";
                } else {
                    $rec = "Deploy scheduled preventive maintenance and equipment overhaul in {$loc} due to high frequency of {$cat} issues ({$count} incidents).";
                }

                $insights[] = [
                    "location" => $loc,
                    "category" => $cat,
                    "total_incidents" => $count,
                    "active_incidents" => $activeCount,
                    "resolved_incidents" => $resolvedCount,
                    "time_window" => "6 months",
                    "severity" => $count >= 10 ? "CRITICAL" : ($count >= 5 ? "HIGH" : "MEDIUM"),
                    "recommendation" => $rec,
                    "sample_incident_numbers" => array_slice(array_column($items, 'incident_number'), 0, 4)
                ];
            }
        }

        usort($insights, fn($a, $b) => $b['total_incidents'] <=> $a['total_incidents']);
        return $insights;
    }

    public static function analyzeIncident(string $title, string $description, string $location = "", array $existingIncidents = []): array {
        $catData = self::classifyIncident($title, $description);
        $deptData = self::suggestDepartment($title, $description, $catData['category'], $location);
        $prioData = self::detectPriority($title, $description, $catData['category']);
        $dupData = self::detectDuplicates($title, $description, $existingIncidents);

        $overallConfidence = round(($catData['confidence'] * 0.4 + $deptData['confidence'] * 0.6) * 100, 1);
        $summary = "{$deptData['code']} department incident reported in " . ($location ?: "campus") . ": '{$title}'. Classified as {$prioData['priority']} priority and routed for automated dispatch.";

        return [
            "suggested_category" => $catData['category'],
            "suggested_department_code" => $deptData['code'],
            "suggested_location_block" => $location ?: "{$deptData['code']} Block",
            "suggested_priority" => $prioData['priority'],
            "confidence_score" => $overallConfidence,
            "reasoning" => "{$catData['reason']} {$deptData['reason']} {$prioData['reason']}",
            "summary" => $summary,
            "requires_admin_review" => $prioData['requires_review'],
            "is_duplicate" => $dupData['is_duplicate'],
            "duplicate_of_incident_id" => $dupData['duplicate_of_incident_id'],
            "duplicate_incident_number" => $dupData['duplicate_incident_number'],
            "duplicate_similarity" => round($dupData['similarity'] * 100, 1),
            "related_reports_count" => $dupData['related_reports_count']
        ];
    }
}

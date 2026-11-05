<?php

namespace ResolveAI\Database;

require_once __DIR__ . '/Database.php';
require_once dirname(__DIR__) . '/services/AIService.php';
require_once dirname(__DIR__) . '/services/ResolutionService.php';

use ResolveAI\Services\AIService;
use PDO;

$config = require dirname(__DIR__) . '/config/config.php';
Database::init($config);
$db = Database::getConnection();

echo "Initializing ResolveAI database schema...\n";
$driver = Database::getDriver();
$schemaSql = Database::getSchemaSql($driver);
$db->exec($schemaSql);

// Clear existing tables
$tables = ['ai_chat_messages', 'knowledge_articles', 'notifications', 'feedback', 'incident_images', 'incident_updates', 'ai_analysis', 'incidents', 'staff_profiles', 'locations', 'departments', 'users'];
foreach ($tables as $tbl) {
    $db->exec("DELETE FROM {$tbl};");
    if ($driver === 'pgsql') {
        try {
            $db->exec("ALTER SEQUENCE IF EXISTS {$tbl}_id_seq RESTART WITH 1;");
        } catch (\Throwable $e) {}
    }
}

echo "Seeding Departments...\n";
$departments = [
    ["CSE", "Computer Science and Engineering", "CSE Block", "cse.dept@smartcampus.local", "Dr. Aris Thorne"],
    ["Chemical", "Chemical Engineering", "Chemical Block", "chemical.dept@smartcampus.local", "Dr. Eleanor Vance"],
    ["Biomedical", "Biomedical Engineering", "Biomedical Block", "biomedical.dept@smartcampus.local", "Dr. Sarah Lin"],
    ["Civil", "Civil Engineering", "Civil Block", "civil.dept@smartcampus.local", "Dr. Marcus Brody"],
    ["Mechanical", "Mechanical Engineering", "Mechanical Block", "mechanical.dept@smartcampus.local", "Dr. Robert Sterling"],
    ["CSBS", "Computer Science and Business Systems", "CSBS Block", "csbs.dept@smartcampus.local", "Dr. Anita Desai"],
    ["Mechatronics", "Mechatronics Engineering", "Mechatronics Block", "mechatronics.dept@smartcampus.local", "Dr. Viktor Reinhardt"],
    ["AIDS", "Artificial Intelligence and Data Science", "AIDS Block", "aids.dept@smartcampus.local", "Dr. Kaelen Chen"],
    ["EE", "Electrical Engineering", "EE Block", "ee.dept@smartcampus.local", "Dr. Samuel O'Connor"],
];

$deptMap = [];
$insDept = $db->prepare("INSERT INTO departments (code, name, building_block, contact_email, head_of_department, description) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($departments as $d) {
    $insDept->execute([$d[0], $d[1], $d[2], $d[3], $d[4], "Academic Department of {$d[1]}."]);
    $deptMap[$d[0]] = Database::lastInsertId($db, 'departments');
}

echo "Seeding Locations...\n";
$locations = [
    ["CSE Block", "Academic Block", $deptMap["CSE"]],
    ["Chemical Block", "Academic Block", $deptMap["Chemical"]],
    ["Biomedical Block", "Academic Block", $deptMap["Biomedical"]],
    ["Civil Block", "Academic Block", $deptMap["Civil"]],
    ["Mechanical Block", "Academic Block", $deptMap["Mechanical"]],
    ["CSBS Block", "Academic Block", $deptMap["CSBS"]],
    ["Mechatronics Block", "Academic Block", $deptMap["Mechatronics"]],
    ["AIDS Block", "Academic Block", $deptMap["AIDS"]],
    ["EE Block", "Academic Block", $deptMap["EE"]],
    ["Main Block", "Academic Block", null],
    ["Library", "Central Facility", null],
    ["Central Laboratory", "Central Facility", null],
    ["Hostel", "Residential", null],
    ["Canteen", "Student Amenities", null],
    ["Auditorium", "Campus Hall", null],
    ["Seminar Hall", "Campus Hall", null],
    ["Sports Ground", "Outdoor Amenities", null],
    ["Parking Area", "Campus Infrastructure", null],
    ["Administrative Block", "Administrative", null],
];

$locMap = [];
$insLoc = $db->prepare("INSERT INTO locations (name, building_type, department_id, description) VALUES (?, ?, ?, ?)");
foreach ($locations as $l) {
    $insLoc->execute([$l[0], $l[1], $l[2], "{$l[0]} campus area."]);
    $locMap[$l[0]] = Database::lastInsertId($db, 'locations');
}

echo "Seeding Users...\n";
$insUser = $db->prepare("INSERT INTO users (email, password_hash, full_name, role, phone, avatar) VALUES (?, ?, ?, ?, ?, ?)");

// Admin
$insUser->execute([
    "admin@smartcampus.local",
    password_hash("Admin@123", PASSWORD_BCRYPT),
    "Alex Vance (Campus Admin)",
    "admin",
    "+91 98765 00001",
    "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150"
]);
$adminId = Database::lastInsertId($db, 'users');

// Staff / Faculty Coordinators (Emails matching Department Code)
$staffUsers = [
    ["cse.staff@smartcampus.local", "CSE Faculty Coordinator", "CSE", "Cloud Computing & Lab Networks"],
    ["cse.faculty@smartcampus.local", "CSE Faculty Head", "CSE", "Systems & Networks"],
    ["chemical.staff@smartcampus.local", "Chemical Faculty Coordinator", "Chemical", "Reaction Engineering & Fume Hoods"],
    ["chemical.faculty@smartcampus.local", "Chemical Faculty Head", "Chemical", "Chemical Safety"],
    ["biomedical.staff@smartcampus.local", "Biomedical Faculty Coordinator", "Biomedical", "Biomedical Instrumentation"],
    ["biomedical.faculty@smartcampus.local", "Biomedical Faculty Head", "Biomedical", "Biosensors & Clinical Labs"],
    ["civil.staff@smartcampus.local", "Civil Faculty Coordinator", "Civil", "Structural & Concrete Labs"],
    ["civil.faculty@smartcampus.local", "Civil Faculty Head", "Civil", "Infrastructure Facilities"],
    ["mechanical.staff@smartcampus.local", "Mechanical Faculty Coordinator", "Mechanical", "CNC & Thermodynamics Lab"],
    ["mechanical.faculty@smartcampus.local", "Mechanical Faculty Head", "Mechanical", "Workshop Engineering"],
    ["csbs.staff@smartcampus.local", "CSBS Faculty Coordinator", "CSBS", "Business Analytics Lab"],
    ["csbs.faculty@smartcampus.local", "CSBS Faculty Head", "CSBS", "Enterprise Systems"],
    ["mechatronics.staff@smartcampus.local", "Mechatronics Faculty Coordinator", "Mechatronics", "Robotics & PLC Automation"],
    ["mechatronics.faculty@smartcampus.local", "Mechatronics Faculty Head", "Mechatronics", "Automation Systems"],
    ["aids.staff@smartcampus.local", "AIDS Faculty Coordinator", "AIDS", "Deep Learning GPU Clusters"],
    ["aids.faculty@smartcampus.local", "AIDS Faculty Head", "AIDS", "AI Systems Architecture"],
    ["ee.staff@smartcampus.local", "EE Faculty Coordinator", "EE", "Power Systems & Substation Panels"],
    ["ee.faculty@smartcampus.local", "EE Faculty Head", "EE", "Electrical Grid & Power"],
];

$staffMap = [];
$insStaffProf = $db->prepare("INSERT INTO staff_profiles (user_id, department_id, specialization) VALUES (?, ?, ?)");
foreach ($staffUsers as $s) {
    $insUser->execute([
        $s[0],
        password_hash("Staff@123", PASSWORD_BCRYPT),
        $s[1],
        "staff",
        "+91 98765 000" . rand(10, 99),
        "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150"
    ]);
    $uid = Database::lastInsertId($db, 'users');
    if (!isset($staffMap[$s[2]])) {
        $staffMap[$s[2]] = $uid;
    }
    $insStaffProf->execute([$uid, $deptMap[$s[2]], $s[3]]);
}

// Students (Emails matching Department Code + General Demo Student)
$insUser->execute([
    "student@smartcampus.local",
    password_hash("Student@123", PASSWORD_BCRYPT),
    "Jordan Lee (Student)",
    "student",
    "+91 98765 11111",
    "https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150"
]);
$studentId = Database::lastInsertId($db, 'users');

$deptStudents = [
    ["cse.student@smartcampus.local", "Aarav Sharma (CSE Student)"],
    ["chemical.student@smartcampus.local", "Priya Patel (Chemical Student)"],
    ["biomedical.student@smartcampus.local", "Rohan Verma (Biomedical Student)"],
    ["civil.student@smartcampus.local", "Ananya Iyer (Civil Student)"],
    ["mechanical.student@smartcampus.local", "Kabir Singh (Mechanical Student)"],
    ["csbs.student@smartcampus.local", "Diya Nair (CSBS Student)"],
    ["mechatronics.student@smartcampus.local", "Vikram Mehta (Mechatronics Student)"],
    ["aids.student@smartcampus.local", "Sneha Reddy (AIDS Student)"],
    ["ee.student@smartcampus.local", "Aditya Joshi (EE Student)"],
    ["priya.sharma@smartcampus.local", "Priya Sharma"],
    ["rohit.verma@smartcampus.local", "Rohit Verma"],
    ["david.miller@smartcampus.local", "David Miller"],
];
$studentIds = [$studentId];
foreach ($deptStudents as $ds) {
    $insUser->execute([
        $ds[0],
        password_hash("Student@123", PASSWORD_BCRYPT),
        $ds[1],
        "student",
        "+91 98765 222" . rand(10, 99),
        null
    ]);
    $studentIds[] = Database::lastInsertId($db, 'users');
}

echo "Seeding Knowledge Base (Resolution Intelligence)...\n";
$knowledgeArticles = [
    [
        "How to reset your VPN password",
        "IT / Network",
        "CSE",
        "Step-by-step procedure to reset campus OpenVPN and AnyConnect credentials via self-service portal.",
        "1. Navigate to https://vpn-portal.campus.local\n2. Sign in with student roll number and date of birth\n3. Click 'Regenerate OTP' and enter verification SMS code\n4. Set new 12-character passphrase\n5. Clear client DNS cache: 'ipconfig /flushdns'",
        92,
        142,
        "vpn, password, reset, access, network, remote"
    ],
    [
        "Multi-factor authentication setup",
        "IT / Network",
        "CSE",
        "Configuring Google Authenticator or Microsoft Authenticator for campus Single Sign-On (SSO).",
        "1. Open campus portal Security tab\n2. Select 'Add Authenticator App'\n3. Scan QR code using mobile authenticator\n4. Enter the 6-digit one-time verification token\n5. Save offline recovery emergency codes",
        89,
        118,
        "mfa, 2fa, sso, login, authentication, otp"
    ],
    [
        "Printer not responding troubleshooting",
        "Equipment",
        "CSE",
        "Resolving offline print queue spooler locks across department network printers.",
        "1. Check if printer displays error codes or paper jam in Tray 2\n2. Open Windows Services (services.msc)\n3. Locate 'Print Spooler' service and click 'Restart'\n4. Verify IP ping to printer gateway\n5. Re-send print job",
        87,
        96,
        "printer, spooler, paper jam, hardware, equipment"
    ],
    [
        "Email configuration for mobile devices",
        "IT / Network",
        "CSE",
        "Setting up campus IMAP/SMTP mail accounts on iOS Mail and Android Gmail apps.",
        "1. Add account type 'Exchange' or 'IMAP'\n2. Inbound server: mail.campus.local (Port 993, SSL/TLS)\n3. Outbound server: smtp.campus.local (Port 587, STARTTLS)\n4. Authentication: Full campus email address and password\n5. Accept server root SSL certificate",
        85,
        84,
        "email, mobile, imap, smtp, outlook, mail, sync"
    ],
    [
        "Campus Wi-Fi connectivity and DHCP renewal",
        "IT / Network",
        "CSE",
        "Fixing 'No Internet, Secured' or IP address conflict errors on Campus-Secure Wi-Fi.",
        "1. Disconnect and select 'Forget Network' for Campus-Secure\n2. Open command prompt and run: 'ipconfig /release' then 'ipconfig /renew'\n3. Reconnect and select EAP method 'PEAP', phase 2 authentication 'MSCHAPV2'\n4. Enter institutional credentials",
        94,
        210,
        "wifi, dhcp, lan, connection, internet, ip address"
    ],
    [
        "Chemical fume hood airflow alarm reset",
        "Laboratory",
        "Chemical",
        "Safely inspecting and calibrating face velocity sensors on lab fume hoods.",
        "1. Inspect sash height (must be below 18 inches marker)\n2. Verify exhaust damper vane switch is open\n3. Press 'Silence / Mute' on control panel for 3 seconds\n4. If red airflow beacon persists, evacuate lab and call Chemical safety desk",
        91,
        64,
        "fume, hood, airflow, alarm, chemical, laboratory, safety"
    ],
    [
        "High voltage panel circuit trip reset guidelines",
        "Electrical",
        "EE",
        "Standard operating procedure for checking tripped circuit breakers in sub-panels.",
        "1. Inspect panel for signs of scorching or smoke\n2. Switch breaker lever firmly to full OFF position\n3. Switch firmly to ON position until audible click occurs\n4. If breaker trips immediately, leave locked-out and flag EE emergency team",
        95,
        152,
        "breaker, electrical, power, outage, panel, voltage, trip"
    ],
    [
        "PHP & Apache lab environment localhost repair",
        "IT / Network",
        "CSE",
        "Resolving port 80/443 conflicts and missing PHP extension errors in web dev labs.",
        "1. Check if IIS or Skype is locking port 80: 'netstat -ano | findstr :80'\n2. Update Apache httpd.conf Listen to 8080 if port 80 is occupied\n3. Enable required extensions in php.ini (pdo_sqlite, curl, mbstring)\n4. Restart Apache service and verify localhost:8080",
        93,
        78,
        "php, apache, localhost, web development, xampp, port conflict"
    ]
];

$insArt = $db->prepare("INSERT INTO knowledge_articles (title, category, department_code, summary, solution_steps, usefulness_pct, deflection_count, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($knowledgeArticles as $ka) {
    $insArt->execute([$ka[0], $ka[1], $ka[2], $ka[3], $ka[4], $ka[5], $ka[6], $ka[7]]);
}

echo "Seeding Incidents & AI Classifications...\n";
$seedIncidents = [
    [
        "Wi-Fi connection down in CSE Block Lab 3",
        "Students in Lab 3 are unable to connect to the campus Wi-Fi network. The SSID appears but DHCP fails to assign IP addresses, blocking practical exam submissions.",
        "IT / Network",
        "HIGH",
        "IN_PROGRESS",
        "CSE Block",
        "Lab 3",
        "CSE",
        "Investigating DHCP scope pool exhaustion on CSE VLAN 40."
    ],
    [
        "Fume hood exhaust ventilation not running in Chemical Synthesis Lab",
        "Strong chemical odor detected. The primary fume extraction hood exhaust motor is unresponsive upon flipping the switch.",
        "Laboratory",
        "CRITICAL",
        "ASSIGNED",
        "Chemical Block",
        "Lab 201",
        "Chemical",
        null
    ],
    [
        "Broken ECG calibration leads in Biomedical Instrumentation Room",
        "Lead II and Lead III cables on the physiological monitor produce high noise artifacts and intermittent signal loss during sensor testing.",
        "Laboratory",
        "MEDIUM",
        "REPORTED",
        "Biomedical Block",
        "Room 104",
        "Biomedical",
        null
    ],
    [
        "Plumbing water leakage from ceiling in Civil Block Restroom",
        "Continuous water dripping from the upper ceiling slab over the washbasin area creating water puddle and slip hazard.",
        "Plumbing",
        "HIGH",
        "RESOLVED",
        "Civil Block",
        "2nd Floor",
        "Civil",
        "Replaced damaged PVC elbow joint in the overhead wastewater riser pipe."
    ],
    [
        "CNC Lathe 3 emergency stop button jammed",
        "Emergency shutoff push-button on lathe 3 is stuck in recessed position and fails to spring return when twisted clockwise.",
        "Equipment",
        "HIGH",
        "IN_PROGRESS",
        "Mechanical Block",
        "Workshop A",
        "Mechanical",
        "Ordered replacement industrial contact block switch."
    ],
    [
        "Tableau desktop analytics license expired in CSBS Lab",
        "Workstations 15 through 30 show error 14 licensing server communication failed when launching business analytics practicals.",
        "Classroom",
        "MEDIUM",
        "RESOLVED",
        "CSBS Block",
        "Room 402",
        "CSBS",
        "Updated network license manager host address and refreshed FlexNet tokens."
    ],
    [
        "Robotic Arm 6-axis servo actuator jitter in Mechatronics Cell",
        "Axis 3 elbow motor vibrates excessively under 2kg payload during automated pick-and-place sequence.",
        "Equipment",
        "MEDIUM",
        "ASSIGNED",
        "Mechatronics Block",
        "Automation Lab",
        "Mechatronics",
        null
    ],
    [
        "NVIDIA A100 GPU workstation thermal throttling in AIDS Cluster",
        "Node 4 GPU 2 temperature reaches 91C within 4 minutes of initiating PyTorch distributed training, throttling clock speeds to 400MHz.",
        "IT / Network",
        "HIGH",
        "RESOLVED",
        "AIDS Block",
        "Server Room",
        "AIDS",
        "Cleaned dust filtration mesh and replaced thermal paste on heatsink plate."
    ],
    [
        "Substation panel circuit breaker trip in EE Block Ground Floor",
        "Audible pop and blackout across eastern corridor lights and 3 lecture rooms following monsoon thunderstorm.",
        "Electrical",
        "CRITICAL",
        "RESOLVED",
        "EE Block",
        "Panel Room G",
        "EE",
        "Inspected insulation resistance, replaced blown 63A ceramic fuse, and re-energized feeder."
    ],
    [
        "Projector HDMI port damaged in Main Seminar Hall",
        "Projection screen flickers magenta when plugging laptop into the podium HDMI wall plate.",
        "Classroom",
        "LOW",
        "REPORTED",
        "Seminar Hall",
        "Podium",
        "CSE",
        null
    ],
    [
        "Campus Wi-Fi connectivity issues in CSE Block 2nd floor",
        "No network connection or internet access available in CSE Block corridor and faculty rooms.",
        "IT / Network",
        "HIGH",
        "REPORTED",
        "CSE Block",
        "2nd Floor",
        "CSE",
        null
    ]
];

$insInc = $db->prepare("
    INSERT INTO incidents (
        incident_number, title, description, category, priority, status,
        student_id, is_anonymous, department_id, assigned_staff_id, location_name, room_lab,
        resolution_notes, resolution_proof_url, created_at, resolved_at, sla_due_at, resolution_worked
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insAI = $db->prepare("
    INSERT INTO ai_analysis (
        incident_id, suggested_category, suggested_department_code, suggested_location_block,
        suggested_priority, confidence_score, reasoning, summary, requires_admin_review,
        is_duplicate, duplicate_of_incident_id, duplicate_similarity, related_reports_count
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insUp = $db->prepare("INSERT INTO incident_updates (incident_id, user_id, update_type, notes, created_at) VALUES (?, ?, ?, ?, ?)");
$insFb = $db->prepare("INSERT INTO feedback (incident_id, student_id, rating, comments, created_at) VALUES (?, ?, ?, ?, ?)");

$incCounter = 1001;
$createdIncIds = [];

foreach ($seedIncidents as $si) {
    $incNum = "INC-" . $incCounter++;
    $stuId = $studentIds[array_rand($studentIds)];
    $deptId = $deptMap[$si[7]] ?? $deptMap["CSE"];
    $staffId = $staffMap[$si[7]] ?? null;
    $status = $si[4];

    $createdAt = date('Y-m-d H:i:s', strtotime("-" . rand(1, 10) . " days"));
    $resolvedAt = in_array($status, ['RESOLVED', 'CLOSED']) ? date('Y-m-d H:i:s', strtotime($createdAt . " +" . rand(2, 8) . " hours")) : null;
    $slaDueAt = date('Y-m-d H:i:s', strtotime($createdAt . " +24 hours"));
    $resWorked = in_array($status, ['RESOLVED', 'CLOSED']) ? 1 : null;

    $insInc->execute([
        $incNum,
        $si[0],
        $si[1],
        $si[2],
        $si[3],
        $status,
        $stuId,
        $deptId,
        $staffId,
        $si[5],
        $si[6],
        $si[8],
        $si[8] ? "https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=400" : null,
        $createdAt,
        $resolvedAt,
        $slaDueAt,
        $resWorked
    ]);

    $incId = Database::lastInsertId($db, 'incidents');
    $createdIncIds[] = $incId;

    $conf = rand(88, 96) / 100.0;
    $isDup = ($si[0] === "Campus Wi-Fi connectivity issues in CSE Block 2nd floor");
    $dupOfId = $isDup ? $createdIncIds[0] : null;

    $insAI->execute([
        $incId,
        $si[2],
        $si[7],
        $si[5],
        $si[3],
        $conf,
        "Domain keywords detected matching {$si[2]} for {$si[7]} department.",
        "Incident reported in {$si[5]}: '{$si[0]}'. Priority estimated as {$si[3]}.",
        ($si[3] === 'CRITICAL' ? 1 : 0),
        ($isDup ? 1 : 0),
        $dupOfId,
        ($isDup ? 0.88 : 0.0),
        ($isDup ? 1 : 0)
    ]);

    $insUp->execute([$incId, $stuId, 'REPORTED', 'Ticket created by student.', $createdAt]);
    if ($status !== 'REPORTED') {
        $insUp->execute([$incId, $staffId, 'ASSIGNMENT', "Assigned to {$si[7]} coordinator.", date('Y-m-d H:i:s', strtotime($createdAt . " +30 minutes"))]);
    }
    if ($status === 'RESOLVED') {
        $insUp->execute([$incId, $staffId, 'RESOLUTION', "Resolved: {$si[8]}", $resolvedAt]);
        $insFb->execute([$incId, $stuId, rand(4, 5), "Prompt resolution and clear diagnostic notes. Thank you!", date('Y-m-d H:i:s', strtotime($resolvedAt . " +1 hour"))]);
    }
}

echo "Database seeding completed successfully!\n";
echo "Demo Accounts:\n";
echo "  Admin:   admin@smartcampus.local / Admin@123\n";
echo "  Staff:   cse.staff@smartcampus.local / Staff@123\n";
echo "  Student: student@smartcampus.local / Student@123\n";

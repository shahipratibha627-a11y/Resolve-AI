<?php

namespace ResolveAI\Database;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;
    private static array $config = [];
    private static string $activeDriver = 'sqlite';
    private static bool $isInitializing = false;

    public static function init(array $config): void {
        self::$config = $config;
    }

    public static function getDriver(): string {
        return self::$activeDriver;
    }

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                self::$config = require dirname(__DIR__) . '/config/config.php';
            }

            $dbConfig = self::$config['database'];
            $rawDriver = strtolower($dbConfig['driver'] ?? 'sqlite');
            $driver = in_array($rawDriver, ['pgsql', 'postgres', 'postgresql']) ? 'pgsql' : $rawDriver;
            self::$activeDriver = $driver;

            try {
                if ($driver === 'sqlite') {
                    $sqlitePath = $dbConfig['sqlite_path'];
                    $dir = dirname($sqlitePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    self::$instance = new PDO("sqlite:" . $sqlitePath);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                    self::$instance->exec("PRAGMA foreign_keys = ON;");
                } elseif ($driver === 'pgsql') {
                    $dsn = sprintf(
                        "pgsql:host=%s;port=%s;dbname=%s",
                        $dbConfig['host'],
                        $dbConfig['port'],
                        $dbConfig['database']
                    );
                    if (!empty($dbConfig['sslmode'])) {
                        $dsn .= ";sslmode=" . $dbConfig['sslmode'];
                    }
                    self::$instance = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                } elseif ($driver === 'mysql') {
                    $dsn = sprintf(
                        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                        $dbConfig['host'],
                        $dbConfig['port'],
                        $dbConfig['database']
                    );
                    self::$instance = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                } else {
                    throw new Exception("Unsupported database driver: " . $driver);
                }

                // Auto-initialize schema & default data at runtime if database is empty
                self::ensureSchemaInitialized(self::$instance, $driver);

            } catch (PDOException $e) {
                throw new Exception("Database Connection Error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Cross-database safe lastInsertId
     */
    public static function lastInsertId(PDO $db, string $table = '', string $column = 'id'): int {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql' && !empty($table)) {
            try {
                $seq = "{$table}_{$column}_seq";
                $id = $db->lastInsertId($seq);
                if ($id) {
                    return (int)$id;
                }
            } catch (\Throwable $e) {
                // Fallback to standard lastInsertId
            }
        }
        return (int)$db->lastInsertId();
    }

    /**
     * Get SQL schema translated for the target database driver
     */
    public static function getSchemaSql(string $driver = 'sqlite'): string {
        $schemaPath = __DIR__ . '/schema.sql';
        if (!file_exists($schemaPath)) {
            return '';
        }
        $sql = file_get_contents($schemaPath);

        if ($driver === 'pgsql') {
            // Replace SQLite AUTOINCREMENT primary key with PostgreSQL SERIAL PRIMARY KEY
            $sql = preg_replace('/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i', 'SERIAL PRIMARY KEY', $sql);
            // Replace DATETIME with standard TIMESTAMP
            $sql = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $sql);
        }

        return $sql;
    }

    /**
     * Checks if tables exist; if not, applies schema and seeds default data at runtime.
     */
    public static function ensureSchemaInitialized(PDO $db, string $driver): void {
        if (self::$isInitializing) {
            return;
        }
        self::$isInitializing = true;

        try {
            $tableExists = false;
            if ($driver === 'pgsql') {
                $stmt = $db->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'departments'");
                $tableExists = (bool)($stmt && $stmt->fetchColumn());
            } else {
                $stmt = $db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='departments'");
                $tableExists = (bool)($stmt && $stmt->fetchColumn());
            }

            if (!$tableExists) {
                $schemaSql = self::getSchemaSql($driver);
                if (!empty($schemaSql)) {
                    $db->exec($schemaSql);
                }
                self::seedInitialData($db, $driver);
            }
        } catch (\Throwable $e) {
            error_log("ResolveAI schema initialization notice: " . $e->getMessage());
        } finally {
            self::$isInitializing = false;
        }
    }

    /**
     * Seeds initial demo departments, locations, users, and runbooks if users table is empty.
     */
    public static function seedInitialData(PDO $db, string $driver = 'sqlite'): void {
        try {
            // Check if users already seeded
            $countUsers = 0;
            try {
                $countUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            } catch (\Throwable $e) {
                return;
            }

            if ($countUsers > 0) {
                return; // Existing data preserved
            }

            // Seed Departments
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
                $deptMap[$d[0]] = self::lastInsertId($db, 'departments');
            }

            // Seed Locations
            $locations = [
                ["CSE Block", "Academic Block", $deptMap["CSE"] ?? null],
                ["Chemical Block", "Academic Block", $deptMap["Chemical"] ?? null],
                ["Biomedical Block", "Academic Block", $deptMap["Biomedical"] ?? null],
                ["Civil Block", "Academic Block", $deptMap["Civil"] ?? null],
                ["Mechanical Block", "Academic Block", $deptMap["Mechanical"] ?? null],
                ["CSBS Block", "Academic Block", $deptMap["CSBS"] ?? null],
                ["Mechatronics Block", "Academic Block", $deptMap["Mechatronics"] ?? null],
                ["AIDS Block", "Academic Block", $deptMap["AIDS"] ?? null],
                ["EE Block", "Academic Block", $deptMap["EE"] ?? null],
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

            $insLoc = $db->prepare("INSERT INTO locations (name, building_type, department_id, description) VALUES (?, ?, ?, ?)");
            foreach ($locations as $l) {
                $insLoc->execute([$l[0], $l[1], $l[2], "{$l[0]} campus area."]);
            }

            // Seed Users
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

            // Staff
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

            $insStaffProf = $db->prepare("INSERT INTO staff_profiles (user_id, department_id, specialization) VALUES (?, ?, ?)");
            foreach ($staffUsers as $s) {
                $insUser->execute([
                    $s[0],
                    password_hash("Staff@123", PASSWORD_BCRYPT),
                    $s[1],
                    "staff",
                    "+91 98765 00099",
                    "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150"
                ]);
                $uid = self::lastInsertId($db, 'users');
                if (isset($deptMap[$s[2]])) {
                    $insStaffProf->execute([$uid, $deptMap[$s[2]], $s[3]]);
                }
            }

            // Student Demo Users
            $students = [
                ["student@smartcampus.local", "Jordan Lee (Student)"],
                ["cse.student@smartcampus.local", "Aarav Sharma (CSE)"],
                ["chemical.student@smartcampus.local", "Priya Patel (Chemical)"],
                ["biomedical.student@smartcampus.local", "Rohan Verma (Biomedical)"],
                ["civil.student@smartcampus.local", "Ananya Iyer (Civil)"],
                ["mechanical.student@smartcampus.local", "Kabir Singh (Mechanical)"],
                ["csbs.student@smartcampus.local", "Diya Nair (CSBS)"],
                ["mechatronics.student@smartcampus.local", "Vikram Mehta (Mechatronics)"],
                ["aids.student@smartcampus.local", "Sneha Reddy (AIDS)"],
                ["ee.student@smartcampus.local", "Aditya Joshi (EE)"],
            ];

            foreach ($students as $st) {
                $insUser->execute([
                    $st[0],
                    password_hash("Student@123", PASSWORD_BCRYPT),
                    $st[1],
                    "student",
                    "+91 98765 11111",
                    "https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150"
                ]);
            }

            // Seed Knowledge Articles
            $articles = [
                ["Campus Wi-Fi & Eduroam Authentication Fix", "IT / Network", "CSE", "Resolve 802.1x authentication failures, expired security certificates, and DHCP allocation timeouts on personal laptops.", "1. Forget the network 'SmartCampus-Secure' or 'Eduroam' in Wi-Fi settings.\n2. Reconnect and select EAP Method: PEAP with Phase 2: MSCHAPv2.\n3. Enter your institutional email and university portal password.\n4. Accept the campus root CA SSL certificate when prompted.", 94, 68, "wifi, eduroam, network, authentication, internet, certificate"],
                ["Lab Workstation Ubuntu Dual-Boot Network Setup", "IT / Network", "CSE", "Restore network connectivity on dual-boot Ubuntu lab PCs when Ethernet interface fails to acquire DHCP lease.", "1. Open terminal and run: sudo systemctl restart NetworkManager\n2. Verify static DNS assignment: cat /etc/resolv.conf\n3. Flush IP route cache: sudo ip route flush cache\n4. Reconnect the RJ-45 cable and test ping to 10.0.0.1.", 91, 52, "linux, ubuntu, dhcp, ethernet, cse lab, dual boot"],
                ["Fume Hood Airflow Alarm & Sash Calibration", "Equipment", "Chemical", "Procedure to silence false low-velocity airflow alarms and calibrate face velocity sensors in Organic Chemistry Labs.", "1. Ensure the vertical glass sash is positioned below the designated 18-inch safety arrow.\n2. Inspect the baffle exhaust ports at the back of the hood for paper or chemical container obstructions.\n3. Press the 'Mute' button once on the velocity monitor panel.\n4. If airflow reads below 80 FPM after 3 minutes, notify Chemical Department safety coordinator.", 96, 41, "fume hood, airflow, chemical lab, ventilation, alarm, safety"],
                ["Biopac MP36 Physiological Monitor Lead Noise Filter", "Equipment", "Biomedical", "Eliminate 50Hz electrical mains interference and baseline drift in ECG/EMG signal recording channels.", "1. Ensure the subject ground electrode (black lead) is placed over a bony landmark with adequate abrasive gel.\n2. Connect the Biopac unit USB cable directly to the rear motherboard port, avoiding unpowered USB hubs.\n3. In BSL PRO software, enable the 50 Hz Notch Filter and 0.5 Hz High-pass baseline drift filter.\n4. Check that nearby power bricks are at least 1 meter away from sensor cables.", 93, 38, "biopac, ecg, biosensor, electrode, noise, biomedical lab"],
                ["High-Density GPU Node Thermal Throttling Mitigation", "Equipment", "AIDS", "Troubleshoot RTX 4090 / A100 workstation overheating and CUDA out-of-memory errors during distributed PyTorch training.", "1. Run 'nvidia-smi' to identify processes with orphaned VRAM allocations.\n2. Terminate zombie training sessions using: kill -9 <PID>\n3. Check server intake fan speeds: sudo ipmitool sensor reading 'Fan1'\n4. Ensure the secondary rear exhaust duct is clear of cables and lab backpacks.", 95, 62, "gpu, cuda, nvidia, pytorch, deep learning, aids, thermal"],
                ["3-Phase Substation Circuit Breaker Reset Procedure", "Electrical", "EE", "Safety protocol for authorized lab assistants responding to high-inrush overload trips in Electrical Machine Labs.", "1. Turn OFF all connected motor-generator benches and resistive load banks immediately.\n2. Inspect the trip indicator flag on the main distribution panel (check for thermal vs magnetic trip).\n3. Confirm no smell of ozone or burnt insulation in the transformer enclosure.\n4. Reset the MCCB handle firmly to the 'OFF' position before pushing up to 'ON'.", 97, 74, "electrical, circuit breaker, 3 phase, power trip, substation, ee lab"],
                ["CNC Lathe Emergency Stop Reset & Spindle Interlock", "Equipment", "Mechanical", "Clear error code E-Stop active on Fanuc / Siemens controller after clearing metal swarf or tool collision.", "1. Rotate the red Emergency Stop mushroom push-button clockwise until it clicks into the released state.\n2. Close the acrylic safety door completely until the magnetic interlock engages.\n3. Press the blue 'Fault Reset' button on the operator console.\n4. Jog the Z-axis carriage manually away from the chuck by +50mm before restarting spindle.", 92, 47, "cnc, lathe, mechanical workshop, spindle, error, e-stop"],
                ["Water Filter Cartridge & RO Leak Prevention", "Plumbing", "Civil", "Step-by-step remediation for low pressure and water dispenser drip tray overflowing in academic corridors.", "1. Locate the blue quarter-turn inlet shutoff valve behind the cooler and turn perpendicular to pipe.\n2. Empty the drainage collector bowl beneath the pre-sediment filter.\n3. Inspect the push-fit John Guest fittings for crimped Teflon tubing.\n4. Slowly reopen the inlet valve and verify static pressure gauge indicates 40-60 PSI.", 89, 35, "water cooler, filter, plumbing, civil block, leak, pressure"]
            ];

            $insArt = $db->prepare("INSERT INTO knowledge_articles (title, category, department_code, summary, solution_steps, usefulness_pct, deflection_count, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($articles as $a) {
                $insArt->execute($a);
            }

        } catch (\Throwable $e) {
            error_log("ResolveAI initial seeding error: " . $e->getMessage());
        }
    }
}

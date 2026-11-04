# ResolveAI — AI-Powered Issue Detection and Resolution Platform 🛡️⚡

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-indigo.svg)
![React](https://img.shields.io/badge/React-18.3-cyan.svg)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.4-blue.svg)
![Vite](https://img.shields.io/badge/Vite-6.0-purple.svg)

**ResolveAI** is an institutional and enterprise-grade, department-aware issue detection, routing, and resolution platform. It manages the entire workflow from initial problem reporting through AI-powered triage, duplicate detection, automated resolution recommendations, and multi-stage technician lifecycle tracking.

---

## 🌟 The 4 Key Standout Pillars

### 1. 🧠 Smart Issue Classification
- Natural Language Intelligence engine classifies incoming problem reports into distinct categories (`IT / Network`, `Electrical`, `Plumbing`, `Equipment`, `Laboratory`, etc.).
- Predicts urgency and priority (`CRITICAL`, `HIGH`, `MEDIUM`, `LOW`) with an explicit **confidence score** (e.g. `94%`).
- Automatically routes tickets to the appropriate academic or technical department.

### 2. 🔍 Semantic Duplicate Detection
- Compares new tickets against active and historical reports using token-level **Jaccard + Cosine vector similarity**.
- Highlights potential duplicates (threshold $\ge 75\%$) with similarity percentages.
- Clusters historical reports by location and category to detect systemic failure hotspots and preventative engineering advisories.

### 3. 💡 Resolution Intelligence & Effectiveness Tracking
- Recommends verified step-by-step troubleshooting solutions derived from resolved historical cases and the institutional knowledge base.
- **Feedback Loop:** Prompts students and technicians with *"Did this solution solve the problem? [Yes, Worked!] [No, Need Technician]"*, continuously retraining article usefulness rankings.
- Tracks Mean Time to Resolution (MTTR) and enforces real-time SLA deadlines.

### 4. 🖥️ Interactive Service Desk & Operations Center
- Ultra-modern dark-themed operations dashboard matching executive enterprise service desks:
  - **Live Telemetry KPIs:** Open Tickets (1,248), Pending (428), Due Today (128), AI Auto-Resolved (312), Avg Response Time (18m), Resolution Rate (91%).
  - **Interactive Visualizations:** AI Triage donut chart, SLA compliance gauge, Tickets Over Time area chart, and Sentiment trend line.
  - **Docked AI Assistant:** Interactive right-side panel with quick-action prompt chips (`Summarize ticket trends`, `Why are SLA breaches increasing?`, etc.) and real-time conversational assistance.

---

## 🛠️ Technology Stack

| Layer | Technology | Key Capabilities |
|---|---|---|
| **Frontend** | React 18, Vite 6, Tailwind CSS 3 | Modern dark aesthetic, Recharts, Lucide Icons, Axios JWT interceptors |
| **Backend** | PHP 8.3 REST API | Native PDO (SQLite zero-config & PostgreSQL ready), RFC 7519 HMAC SHA-256 JWT |
| **AI / NLP** | Built-in NLP & Vector Algorithms | Tokenization, Cosine + Jaccard similarity, rule heuristics, Gemini API integration |
| **Database** | SQLite / PostgreSQL | Structured relational schema with indexing, audit updates, and feedback tracking |
| **Tooling** | Git, VS Code, Windows Shell | 1-click batch startup scripts |

---

## 📂 Project Directory Structure

```
ResolveAI/
├── backend/
│   ├── config/
│   │   └── config.php          # Database, JWT, and AI provider configurations
│   ├── database/
│   │   ├── Database.php        # PDO connection singleton (SQLite/PostgreSQL)
│   │   ├── schema.sql          # Relational tables schema
│   │   └── seed.php            # Database seeder with realistic campus dataset
│   ├── models/                 # Model abstractions
│   ├── services/
│   │   ├── AIService.php       # Classification, Priority, Vector Similarity, Duplicates
│   │   ├── ResolutionService.php # Resolution Intelligence, verified solutions, feedback
│   │   ├── AIAssistantService.php# Chatbot and telemetry insights engine
│   │   └── JWTService.php      # Token generation and verification
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── IncidentController.php
│   │   ├── AnalyticsController.php
│   │   ├── KnowledgeController.php
│   │   ├── AIAssistantController.php
│   │   └── ...
│   ├── middleware/
│   │   ├── CorsMiddleware.php
│   │   └── AuthMiddleware.php
│   └── public/
│       ├── index.php           # Front controller & REST API router
│       └── router.php          # CLI web server routing
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   │   ├── OperationsHeader.jsx   # Top navigation, search, live status, persona menu
│   │   │   ├── OperationsSidebar.jsx  # Left navigation bar
│   │   │   └── AIAssistantDrawer.jsx  # Docked AI assistant panel
│   │   ├── pages/
│   │   │   ├── OperationsDashboard.jsx# Flagship operations center with Recharts
│   │   │   ├── ReportIncident.jsx     # AI classification & solution preview
│   │   │   ├── IncidentDetails.jsx    # Ticket details & solution feedback
│   │   │   ├── TicketsPage.jsx        # Multi-filter ticket queue
│   │   │   ├── KnowledgePage.jsx      # Resolution intelligence runbooks
│   │   │   ├── ProblemsPage.jsx       # Hotspot & duplicate clustering
│   │   │   └── Login.jsx              # Sign-in with 1-click demo chips
│   │   ├── context/
│   │   │   └── AuthContext.jsx
│   │   ├── services/
│   │   │   └── api.js                 # Axios API client
│   │   ├── App.jsx
│   │   └── main.jsx
│   ├── package.json
│   └── vite.config.js
├── start_all.bat               # 1-Click launcher for both servers + browser
├── start_backend.bat           # Starts PHP API at http://localhost:5000
├── start_frontend.bat          # Starts Vite dev server at http://localhost:5173
├── open_in_vscode.bat          # 1-Click open in VS Code
└── README.md
```

---

## 🚀 Quickstart Guide

### Option 1: 1-Click Launch (Recommended)
Double-click `start_all.bat`. It will:
1. Ensure ports `5000` and `5173` are clear.
2. Launch the PHP 8.3 Backend at `http://localhost:5000`.
3. Launch the Vite Frontend at `http://localhost:5173`.
4. Automatically open `http://localhost:5173` in your default browser.

### Option 2: Manual Terminal Execution

**Start the PHP Backend:**
```powershell
cd backend\public
php -S 127.0.0.1:5000 router.php
```

**Start the React Frontend:**
```powershell
cd frontend
npm run dev
```

---

## 🔑 Pre-Configured Demo Accounts (Matching Department Codes)

All accounts are pre-configured with standardized emails corresponding directly to each department code:

### Campus Administrator
- **Email:** `admin@smartcampus.local`
- **Password:** `Admin@123`

### Faculty / Department Coordinators (Password: `Staff@123`)
Emails are mapped directly to each academic department code:

| Department Code | Department Name | Faculty Email | Scope |
|---|---|---|---|
| **CSE** | Computer Science & Engineering | `cse.faculty@smartcampus.local` (or `cse.staff@...`) | Programming labs, cloud, network switches |
| **Chemical** | Chemical Engineering | `chemical.faculty@smartcampus.local` | Fume hoods, safety wash, chemical labs |
| **Biomedical** | Biomedical Engineering | `biomedical.faculty@smartcampus.local` | Patient monitors, ECG/sensors, medical equipment |
| **Civil** | Civil Engineering | `civil.faculty@smartcampus.local` | Structural labs, ceiling leaks, plumbing risers |
| **Mechanical** | Mechanical Engineering | `mechanical.faculty@smartcampus.local` | Workshop CNC lathes, heavy tools, cooling systems |
| **CSBS** | Computer Science & Business Systems | `csbs.faculty@smartcampus.local` | Business analytics workstations, ERP labs |
| **Mechatronics** | Mechatronics Engineering | `mechatronics.faculty@smartcampus.local` | 6-axis robotic arms, PLC trainers, automation |
| **AIDS** | Artificial Intelligence & Data Science | `aids.faculty@smartcampus.local` | High-density GPU server clusters, deep learning |
| **EE** | Electrical Engineering | `ee.faculty@smartcampus.local` | Substation panels, transformers, power feeds |

### Students (Password: `Student@123`)
Students also have departmental emails matching the code:

| Department Code | Student Email | Name |
|---|---|---|
| **General** | `student@smartcampus.local` | Jordan Lee (Primary Demo Student) |
| **CSE** | `cse.student@smartcampus.local` | Aarav Sharma |
| **Chemical** | `chemical.student@smartcampus.local` | Priya Patel |
| **Biomedical** | `biomedical.student@smartcampus.local` | Rohan Verma |
| **Civil** | `civil.student@smartcampus.local` | Ananya Iyer |
| **Mechanical** | `mechanical.student@smartcampus.local` | Kabir Singh |
| **CSBS** | `csbs.student@smartcampus.local` | Diya Nair |
| **Mechatronics** | `mechatronics.student@smartcampus.local` | Vikram Mehta |
| **AIDS** | `aids.student@smartcampus.local` | Sneha Reddy |
| **EE** | `ee.student@smartcampus.local` | Aditya Joshi |

---

## 🔌 API Endpoints Summary

- `POST /api/auth/login` — Authenticate and receive JWT token
- `POST /api/auth/register` — Self-register student account
- `GET  /api/auth/demo-accounts` — Fetch pre-configured demo credentials
- `GET  /api/analytics/operations-dashboard` — Full telemetry payload for operations center
- `POST /api/ai/assistant` — Interactive AI assistant chat and telemetry analysis
- `POST /api/incidents/ai-preview` — Real-time AI classification, department suggestion, priority & solutions
- `GET  /api/incidents` — Search and filter tickets
- `GET  /api/incidents/{id}` — Full incident details with AI analysis & recommended solutions
- `POST /api/incidents/{id}/solution-feedback` — Record whether AI solution worked
- `PATCH /api/incidents/{id}/status` — Transition ticket status and record diagnostic notes
- `GET  /api/knowledge` — List verified resolution articles with usefulness ratings

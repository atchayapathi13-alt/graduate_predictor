# 🎓 Graduate Predicting Analysis System

An intelligent, responsive, multi-tiered full-stack web application designed for engineering colleges, academic mentors, and students to analyze student academic performance, technical skills, internship experiences, certifications, projects, and extracurricular participation to generate a **graduate outcome prediction and analytical decision dashboard**.

---

## 📌 Project Overview

The **Graduate Predicting Analysis System** empowers higher education institutions to move from retroactive evaluation to **proactive academic decision support**. By evaluating student performance across five fundamental dimensions—**Academic Performance (40%)**, **Technical Skills (25%)**, **Career Readiness (20%)**, **Soft Skills (10%)**, and **Extracurricular Activities (5%)**—the platform computes transparent performance scores, classifies readiness into three tiers, pinpoints demonstrated strengths, identifies areas requiring improvement, and issues personalized, prioritized recommendations.

> **Important Decision-Support Disclaimer**:  
> *"This analysis is an academic decision-support estimate based on the information provided. It should not be treated as a guaranteed prediction of future academic or employment outcomes."*

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Font Awesome 6.5, Chart.js 4.4
- **Backend**: PHP 8.2+ (Modular OOP architecture, PDO with prepared statements)
- **Database**: MySQL / MariaDB (Normalized 3NF relational schema)
- **Runtime Environment**: XAMPP / Apache / MySQL / VS Code

---

## 👥 User Roles & Capabilities

| Role | Access Level | Primary Features |
| :--- | :--- | :--- |
| **Student** | Personal Portal | Multi-step profile data entry (Academic, 14+ Tech skills, Soft skills, Internships, Projects, Certifications, Hackathons), Real-time readiness meter, Radar charts, Strengths/Weaknesses breakdown, Action recommendations, Printable analysis report, CSV export. |
| **Faculty / Analyst** | Departmental Oversight | Student directory with multi-parameter search/filters, Cohort distribution analytics, At-Risk mentoring roster (attendance shortage, active backlogs, low CGPA), Side-by-side student comparison radar/bar charts, Cohort CSV export. |
| **Administrator** | Institutional Management | System-wide KPI dashboard, Student CRUD management, Faculty management, Department management, Configurable Scoring Weights engine with real-time recalculation, System data volume metrics. |

---

## 🧮 Mathematical Scoring & Prediction Model

### 1. Dimension Calculations

1. **Academic Score (0–100%)**:
   $$\text{Academic Score} = \left(\frac{\min(\text{CGPA}, 10)}{10} \times 50\right) + \left(\frac{\text{Attendance}}{100} \times 25\right) + \left(\frac{\text{Prior Avg}}{100} \times 15\right) + \max(0, 10 - (\text{Backlogs} \times 3))$$

2. **Technical Skill Score (0–100%)**:
   - Technical Skill Rating Average (1–5 scale normalized): 50%
   - Practical Project Portfolio (Count, Complexity, Status): 30%
   - Industry-Recognized Certifications (Level & Volume): 20%

3. **Career Readiness Score (0–100%)**:
   - Industry / Research Internship Completion & Duration: 40%
   - Verified GitHub Repository Project Portfolio: 25%
   - Industry Certifications (AWS, Google, Coursera, NPTEL): 15%
   - Soft Skills & Aptitude Factor: 20%

4. **Soft Skills Score (0–100%)**:
   - Average across Communication, Teamwork, Leadership, Problem Solving, Time Management, Adaptability, and Presentation.

5. **Extracurricular Activities Score (0–100%)**:
   - Hackathon victories, Coding competitions, Technical exhibitions, and Paper presentations.

### 2. Weighted Overall Performance Index
$$\text{Overall Score} = \frac{(S_{\text{acad}} \times W_{\text{acad}}) + (S_{\text{tech}} \times W_{\text{tech}}) + (S_{\text{career}} \times W_{\text{career}}) + (S_{\text{soft}} \times W_{\text{soft}}) + (S_{\text{extra}} \times W_{\text{extra}})}{100}$$

*(Weights are fully configurable in the Admin Settings panel)*

### 3. Readiness Outcome Classification

- **High Readiness**: $\text{Overall Score} \ge 75 \land \text{Backlogs} = 0 \land \text{Attendance} \ge 75\% \land \text{Technical} \ge 65\%$
- **Moderate Readiness**: $\text{Overall Score} \ge 50 \land \text{Backlogs} \le 1$
- **Needs Improvement**: $\text{Overall Score} < 50 \lor \text{Backlogs} > 1 \lor \text{Attendance} < 65\%$

---

## 📁 Project Directory Structure

```
graduate_predictor/
│
├── index.php                      # Modern public landing page
├── login.php                      # Authentication with 1-Click Demo buttons
├── register.php                   # Student registration portal
├── logout.php                     # Secure session termination
│
├── config/
│   ├── database.php               # PDO connection singleton
│   ├── auth.php                   # Session guards, role checks & CSRF tokens
│   └── helper.php                 # Utility functions, badges & settings loader
│
├── classes/
│   ├── AnalysisEngine.php         # 5-Domain weighted calculation engine
│   ├── PredictionEngine.php       # Outcome categorization & transparent confidence
│   └── RecommendationEngine.php   # Dynamic prioritized action synthesizer
│
├── includes/
│   ├── header.php                 # Master HTML head, navigation & topbar
│   ├── sidebar.php                # Role-based sidebar menu
│   ├── footer.php                 # Footer, scripts & modal templates
│   └── flash.php                  # Bootstrap alert flash messages
│
├── student/
│   ├── dashboard.php              # KPI cards, 4 Chart.js charts & summary
│   ├── profile.php                # View/edit student personal details
│   ├── academic.php               # Academic information & semester GPAs
│   ├── skills.php                 # Interactive 1-5 technical & soft skill ratings
│   ├── certifications.php         # Certifications manager with verification URLs
│   ├── internships.php            # Internship details & experience logging
│   ├── projects.php               # Projects portfolio & GitHub tracking
│   ├── extracurricular.php        # Hackathons & activities manager
│   ├── analysis.php               # Mathematical formula & domain breakdown
│   ├── prediction.php             # Outcome category, confidence, strengths/weaknesses
│   ├── recommendations.php        # Prioritized action roadmap with category filter
│   └── report.php                 # Formal printable report & CSV export
│
├── faculty/
│   ├── dashboard.php              # Departmental metrics & cohort charts
│   ├── students.php               # Search & multi-parameter student filter
│   ├── student_detail.php         # In-depth candidate mentoring view
│   ├── at_risk.php                # Students requiring academic intervention
│   ├── comparison.php             # Multi-student comparative radar & bar charts
│   └── export.php                 # Export cohort data to CSV
│
├── admin/
│   ├── dashboard.php              # Institutional command center
│   ├── students.php               # Full Student CRUD management
│   ├── faculty.php                # Full Faculty staff management
│   ├── departments.php            # Academic departments management
│   ├── settings.php               # Configurable scoring weights & threshold engine
│   └── analytics.php              # Database volume metrics & league table
│
├── api/
│   ├── students.php               # REST API for student search & profiles
│   ├── analysis.php               # REST API for scoring calculations
│   ├── prediction.php             # REST API for outcome categorization
│   ├── recommendations.php        # REST API for dynamic recommendations
│   ├── compare.php                # REST API for multi-student chart datasets
│   └── analytics.php              # REST API for system statistics
│
├── assets/
│   ├── css/
│   │   └── style.css              # Custom styles, glassmorphism, responsive & print
│   ├── js/
│   │   ├── main.js                # Sidebar toggle, tooltips & demo login handler
│   │   ├── charts.js              # Reusable Chart.js initialization methods
│   │   └── student-form.js        # Interactive rating buttons & real-time validation
│   └── images/
│       └── logo.svg               # Vector graduation logo
│
├── database/
│   └── graduate_predictor.sql     # Complete schema + 10 realistic seed students
│
└── README.md                      # Comprehensive installation & usage manual
```

---

## 🚀 Setup & Installation Guide (XAMPP)

### Step 1: Start XAMPP Services
1. Open the **XAMPP Control Panel**.
2. Start the **Apache** service.
3. Start the **MySQL** service.

### Step 2: Place Code in XAMPP `htdocs`
The project is configured at:
```
C:\xampp\htdocs\graduate_predictor
```
*(A directory junction is automatically linked to the project codebase)*

### Step 3: Import the MySQL Database
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click **Import** in the top navigation bar.
3. Choose the SQL file located at:
   ```
   C:\xampp\htdocs\graduate_predictor\database\graduate_predictor.sql
   ```
4. Click **Go** / **Import** at the bottom.
5. The database `graduate_predictor` will be created with all 16 normalized tables and 10 pre-loaded student profiles.

Alternatively, import via Command Line:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\graduate_predictor\database\graduate_predictor.sql"
```

### Step 4: Open the Web Application
Open your browser and visit:
```
http://localhost/graduate_predictor/
```

> **Note regarding Port 8080**:  
> If your Apache is configured on port `8080` (e.g. if port 80 is occupied by IIS), access the application at:
> ```
> http://localhost:8080/graduate_predictor/
> ```

---

## 🔑 Demo Login Credentials

The login page includes **Quick 1-Click Demo Buttons** that automatically log you in with one click. You can also sign in manually using:

| Role | Account Name | Email Address | Password |
| :--- | :--- | :--- | :--- |
| **Student (High)** | Aarav Sharma | `student1@graduate.edu` | `Password@123` |
| **Student (Moderate)** | Rohan Verma | `student3@graduate.edu` | `Password@123` |
| **Student (At-Risk)** | Vikram Singh | `student5@graduate.edu` | `Password@123` |
| **Faculty (CSE HOD)** | Dr. Ramesh Kumar | `faculty.cse@graduate.edu` | `Password@123` |
| **Faculty (IT Prof)** | Dr. S. Meenakshi | `faculty.it@graduate.edu` | `Password@123` |
| **Administrator** | System Administrator | `admin@graduate.edu` | `Password@123` |

---

## 🧪 Testing Checklist

- [x] **Registration**: Register a new student account at `register.php`. Verify automatic creation of baseline records.
- [x] **Authentication**: Sign in via `login.php`. Test role-based redirection to student, faculty, and admin dashboards.
- [x] **Student Data Entry**:
  - Update Academic Info at `student/academic.php`. Test CGPA & attendance validation.
  - Update Technical Skills at `student/skills.php` using interactive 1–5 badges.
  - Add certifications, internships, projects, and extracurricular activities.
- [x] **Scoring & Prediction Engine**: Verify that modifying academic records or projects immediately updates the Overall Score, Readiness Category, Strengths, Weaknesses, and Recommendations.
- [x] **Visual Analytics**: Confirm that all 5 Chart.js visualizations (Doughnut, Bar, Radar, Line, Comparison) render smoothly and adapt responsively to mobile/desktop screens.
- [x] **Student Comparison**: In `faculty/comparison.php`, select 2–4 students and observe real-time comparative radar and grouped bar charts.
- [x] **Admin Scoring Weights**: In `admin/settings.php`, adjust weights (e.g. Academic 50%, Tech 20%) and confirm that all students' scores are dynamically recomputed.
- [x] **Reports & Exports**:
  - Open `student/report.php` and click **Print / Save as PDF** (`window.print()`).
  - Click **Download CSV** to receive an offline dataset.
  - In `faculty/export.php`, export the entire cohort dataset.

---

## 🛠️ Common Errors & Troubleshooting

1. **"Database connection failed. Please ensure MySQL is running in XAMPP."**
   - Ensure the MySQL service in XAMPP Control Panel is started (green indicator on port 3306).
   - If MySQL has a custom root password, edit `config/database.php` and specify `$pass = 'your_password';`.

2. **"Port 80 is occupied" in Apache:**
   - In XAMPP, open `httpd.conf` and change `Listen 80` to `Listen 8080`.
   - Access the site at `http://localhost:8080/graduate_predictor/`.

3. **PHP Version Notice:**
   - The application requires PHP 8.0+. Check your version with `php -v`. XAMPP 8.2+ is recommended.

---

## 🔮 Future Enhancements

1. **Scikit-Learn / ONNX Model Deployment**: Train Random Forest / XGBoost classifiers on multi-year institutional placement records and serve inferences via a lightweight REST microservice.
2. **Resume Parser Integration**: Automatic extraction of projects, technical skills, and internships from uploaded PDF resumes using OCR and NLP.
3. **Automated Mentorship Scheduling**: Direct integration with Google Calendar / Outlook for one-click mentoring appointment booking for at-risk candidates.
4. **Alumni Benchmark Matching**: Match current student skill distributions against profiles of alumni working at target companies (e.g. Google, Amazon, Microsoft).

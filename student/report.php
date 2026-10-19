<?php
/**
 * Student Analysis Comprehensive Report
 * Print-Friendly & CSV Exportable
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';
require_once __DIR__ . '/../classes/PredictionEngine.php';
require_once __DIR__ . '/../classes/RecommendationEngine.php';

requireLogin();
$currentUser = getCurrentUser();

$studentId = null;
if (isset($_GET['student_id']) && ($currentUser['role'] === 'faculty' || $currentUser['role'] === 'admin')) {
    $studentId = (int) $_GET['student_id'];
} elseif ($currentUser['role'] === 'student') {
    $studentId = $currentUser['student_id'];
} else {
    $studentId = 1; // Default fallback for faculty viewing report without explicit param
}

$db = getDB();

// Fetch student complete record
$stmt = $db->prepare("
    SELECT s.*, u.name, u.email, u.phone, d.dept_name, d.dept_code,
           ar.tenth_percentage, ar.twelfth_percentage, ar.diploma_percentage,
           ar.current_cgpa, ar.current_semester, ar.num_backlogs, ar.cleared_backlogs,
           ar.attendance_percentage
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

$analysisEngine = new AnalysisEngine();
$predEngine = new PredictionEngine();
$recEngine = new RecommendationEngine();

$scores = $analysisEngine->calculateStudentAnalysis($studentId);
$prediction = $predEngine->predictStudentOutcome($studentId);
$recommendations = $recEngine->generateStudentRecommendations($studentId);

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Student_Report_' . $student['student_id'] . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['GRADUATE PREDICTING ANALYSIS SYSTEM - STUDENT PERFORMANCE REPORT']);
    fputcsv($output, ['Generated Date', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['--- STUDENT INFORMATION ---']);
    fputcsv($output, ['Student ID', $student['student_id']]);
    fputcsv($output, ['Full Name', $student['name']]);
    fputcsv($output, ['Email', $student['email']]);
    fputcsv($output, ['Department', $student['dept_name'] . ' (' . $student['dept_code'] . ')']);
    fputcsv($output, ['Degree', $student['degree']]);
    fputcsv($output, ['Graduation Year', $student['graduation_year']]);
    fputcsv($output, []);
    fputcsv($output, ['--- ACADEMIC METRICS ---']);
    fputcsv($output, ['Current CGPA', $student['current_cgpa']]);
    fputcsv($output, ['Attendance %', $student['attendance_percentage']]);
    fputcsv($output, ['Current Semester', $student['current_semester']]);
    fputcsv($output, ['Active Backlogs', $student['num_backlogs']]);
    fputcsv($output, ['10th Standard %', $student['tenth_percentage']]);
    fputcsv($output, ['12th / Diploma %', $student['twelfth_percentage'] ?: $student['diploma_percentage']]);
    fputcsv($output, []);
    fputcsv($output, ['--- PERFORMANCE SCORES ---']);
    fputcsv($output, ['Overall Score', $scores['overall_score'] . ' / 100']);
    fputcsv($output, ['Academic Score', $scores['academic_score'] . '%']);
    fputcsv($output, ['Technical Score', $scores['technical_score'] . '%']);
    fputcsv($output, ['Career Readiness Score', $scores['career_readiness_score'] . '%']);
    fputcsv($output, ['Soft Skills Score', $scores['soft_skills_score'] . '%']);
    fputcsv($output, ['Extracurricular Score', $scores['extracurricular_score'] . '%']);
    fputcsv($output, []);
    fputcsv($output, ['--- PREDICTION & OUTCOME ---']);
    fputcsv($output, ['Readiness Category', $prediction['category']]);
    fputcsv($output, ['Confidence Index', $prediction['confidence_score'] . '%']);
    fputcsv($output, ['Analytical Summary', $prediction['prediction_notes']]);
    fputcsv($output, []);
    fputcsv($output, ['--- RECOMMENDATIONS ---']);
    foreach ($recommendations as $r) {
        fputcsv($output, [$r['priority'] . ' Priority', $r['category'], $r['title'], $r['description']]);
    }
    fputcsv($output, []);
    fputcsv($output, ['DISCLAIMER', 'This analysis is an academic decision-support estimate based on the information provided. It should not be treated as a guaranteed prediction of future academic or employment outcomes.']);
    fclose($output);
    exit;
}

$pageTitle = 'Performance Report - ' . $student['student_id'];
include __DIR__ . '/../includes/header.php';
?>

<!-- Action Bar (Print / CSV) -->
<div class="d-flex justify-content-between align-items-center mb-4 btn-print-hide">
    <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <div class="d-flex gap-2">
        <a href="<?= baseUrl('student/report.php?student_id=' . $studentId . '&export=csv') ?>" class="btn btn-outline-success rounded-pill px-3 fw-semibold">
            <i class="fas fa-file-csv me-1"></i> Download CSV
        </a>
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-print me-1"></i> Print / Save as PDF
        </button>
    </div>
</div>

<!-- Printable Report Container -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white" id="reportContent">

    <!-- Report Institutional Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom pb-4 mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= baseUrl('assets/images/logo.svg') ?>" alt="Institution Logo" width="56" height="56">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Graduate Predicting Analysis System</h4>
                <div class="text-muted small">Academic Decision Support & Placement Readiness Assessment</div>
            </div>
        </div>
        <div class="text-sm-end">
            <span class="badge bg-light text-dark border font-monospace px-3 py-2">
                REPORT REF: REP-<?= date('Y') ?>-<?= sanitize($student['student_id']) ?>
            </span>
            <div class="text-muted small mt-1">Date of Analysis: <strong><?= date('d M Y, h:i A') ?></strong></div>
        </div>
    </div>

    <!-- Student Bio Summary Grid -->
    <div class="bg-light p-4 rounded-3 mb-4 border">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-user-graduate me-2 text-primary"></i>Candidate Profile Summary</h6>
        <div class="row g-3 small">
            <div class="col-sm-6 col-md-3">
                <span class="text-muted d-block">Student ID</span>
                <strong class="text-dark fs-6"><?= sanitize($student['student_id']) ?></strong>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="text-muted d-block">Full Name</span>
                <strong class="text-dark fs-6"><?= sanitize($student['name']) ?></strong>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="text-muted d-block">Department</span>
                <strong class="text-dark"><?= sanitize($student['dept_name']) ?> (<?= sanitize($student['dept_code']) ?>)</strong>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="text-muted d-block">Degree & Batch</span>
                <strong class="text-dark"><?= sanitize($student['degree']) ?> (<?= sanitize($student['academic_year']) ?>)</strong>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="text-muted d-block">Email Address</span>
                <span class="text-dark"><?= sanitize($student['email']) ?></span>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="text-muted d-block">Phone Number</span>
                <span class="text-dark"><?= sanitize($student['phone'] ?: 'N/A') ?></span>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="text-muted d-block">Current Semester</span>
                <span class="text-dark">Semester <?= $student['current_semester'] ?></span>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="text-muted d-block">Anticipated Graduation</span>
                <span class="text-dark"><?= $student['graduation_year'] ?></span>
            </div>
        </div>
    </div>

    <!-- Outcome Prediction Highlight Box -->
    <div class="p-4 rounded-4 mb-4 border text-center <?= ($prediction['category'] === 'High Readiness') ? 'bg-success bg-opacity-10 border-success' : (($prediction['category'] === 'Moderate Readiness') ? 'bg-warning bg-opacity-10 border-warning' : 'bg-danger bg-opacity-10 border-danger') ?>">
        <div class="small text-uppercase fw-bold text-muted mb-1">PREDICTED OUTCOME CATEGORY</div>
        <h2 class="fw-extrabold mb-1"><?= strtoupper(sanitize($prediction['category'])) ?></h2>
        <p class="text-dark mb-2 max-w-700 mx-auto small"><?= sanitize($prediction['prediction_notes']) ?></p>
        <div class="d-inline-flex align-items-center gap-3">
            <span class="badge bg-white text-dark border px-3 py-1 font-monospace">
                Aggregate Score: <strong><?= number_format($scores['overall_score'], 1) ?>/100</strong>
            </span>
            <span class="badge bg-white text-dark border px-3 py-1 font-monospace">
                Evaluation Confidence: <strong><?= number_format($prediction['confidence_score'], 1) ?>%</strong>
            </span>
        </div>
    </div>

    <!-- Domain Evaluation Table -->
    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-chart-pie me-2 text-primary"></i>Domain Scores & Weights Breakdown</h6>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>Dimension</th>
                    <th class="text-center">Configured Weight</th>
                    <th class="text-center">Score (0-100)</th>
                    <th class="text-center">Weighted Contribution</th>
                    <th>Analytical Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Academic Performance</strong> (CGPA, Attendance, Backlogs)</td>
                    <td class="text-center"><?= $scores['weights']['academic'] ?>%</td>
                    <td class="text-center fw-bold"><?= number_format($scores['academic_score'], 1) ?>%</td>
                    <td class="text-center"><?= number_format(($scores['academic_score'] * $scores['weights']['academic'])/100, 2) ?></td>
                    <td><?= $scores['academic_score'] >= 75 ? '<span class="text-success fw-semibold">Above Benchmark</span>' : '<span class="text-warning fw-semibold">Needs Support</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>Technical & Programming Skills</strong></td>
                    <td class="text-center"><?= $scores['weights']['technical'] ?>%</td>
                    <td class="text-center fw-bold"><?= number_format($scores['technical_score'], 1) ?>%</td>
                    <td class="text-center"><?= number_format(($scores['technical_score'] * $scores['weights']['technical'])/100, 2) ?></td>
                    <td><?= $scores['technical_score'] >= 70 ? '<span class="text-success fw-semibold">Industry Ready</span>' : '<span class="text-warning fw-semibold">Foundation Review</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>Career Readiness & Internships</strong></td>
                    <td class="text-center"><?= $scores['weights']['career'] ?>%</td>
                    <td class="text-center fw-bold"><?= number_format($scores['career_readiness_score'], 1) ?>%</td>
                    <td class="text-center"><?= number_format(($scores['career_readiness_score'] * $scores['weights']['career'])/100, 2) ?></td>
                    <td><?= $scores['career_readiness_score'] >= 70 ? '<span class="text-success fw-semibold">Strong Profile</span>' : '<span class="text-danger fw-semibold">Action Required</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>Soft Skills & Communication</strong></td>
                    <td class="text-center"><?= $scores['weights']['soft_skills'] ?>%</td>
                    <td class="text-center fw-bold"><?= number_format($scores['soft_skills_score'], 1) ?>%</td>
                    <td class="text-center"><?= number_format(($scores['soft_skills_score'] * $scores['weights']['soft_skills'])/100, 2) ?></td>
                    <td><?= $scores['soft_skills_score'] >= 70 ? '<span class="text-success fw-semibold">Competent</span>' : '<span class="text-warning fw-semibold">Practice Advised</span>' ?></td>
                </tr>
                <tr>
                    <td><strong>Extracurricular Activities</strong></td>
                    <td class="text-center"><?= $scores['weights']['extracurricular'] ?>%</td>
                    <td class="text-center fw-bold"><?= number_format($scores['extracurricular_score'], 1) ?>%</td>
                    <td class="text-center"><?= number_format(($scores['extracurricular_score'] * $scores['weights']['extracurricular'])/100, 2) ?></td>
                    <td><?= $scores['extracurricular_score'] >= 60 ? '<span class="text-success fw-semibold">Active Participant</span>' : '<span class="text-muted">Standard</span>' ?></td>
                </tr>
                <tr class="table-light fw-bold">
                    <td>AGGREGATE OVERALL PERFORMANCE SCORE</td>
                    <td class="text-center">100%</td>
                    <td class="text-center text-primary fs-6"><?= number_format($scores['overall_score'], 1) ?>/100</td>
                    <td class="text-center"><?= number_format($scores['overall_score'], 2) ?></td>
                    <td><?= getReadinessBadge($prediction['category']) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Strengths & Improvement Areas Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <h6 class="fw-bold text-success border-bottom pb-2 mb-2"><i class="fas fa-check-circle me-1"></i>Demonstrated Strengths</h6>
            <ul class="list-unstyled small mb-0">
                <?php foreach ($prediction['strengths'] as $s): ?>
                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i><?= sanitize($s) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold text-warning text-dark border-bottom pb-2 mb-2"><i class="fas fa-triangle-exclamation text-warning me-1"></i>Areas Requiring Improvement</h6>
            <ul class="list-unstyled small mb-0">
                <?php foreach ($prediction['weaknesses'] as $w): ?>
                    <li class="mb-1"><i class="fas fa-circle-exclamation text-warning me-2"></i><?= sanitize($w) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Prioritized Recommendations -->
    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i>Prioritized Action Plan</h6>
    <div class="d-flex flex-column gap-2 mb-4">
        <?php foreach ($recommendations as $r): ?>
            <div class="p-2 border rounded-2 bg-light small">
                <div class="d-flex justify-content-between">
                    <strong><?= sanitize($r['title']) ?></strong>
                    <span class="badge bg-secondary-subtle text-secondary"><?= sanitize($r['priority']) ?> Priority</span>
                </div>
                <div class="text-muted mt-1"><?= sanitize($r['description']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Institutional Signatures & Official Disclaimer -->
    <div class="row pt-4 mt-4 border-top text-center text-muted small g-3">
        <div class="col-4">
            <div style="height: 40px;"></div>
            <div class="border-top pt-1">Student Signature</div>
        </div>
        <div class="col-4">
            <div style="height: 40px;"></div>
            <div class="border-top pt-1">Faculty Mentor / Advisor</div>
        </div>
        <div class="col-4">
            <div style="height: 40px;"></div>
            <div class="border-top pt-1">Head of Department / Placement Officer</div>
        </div>
    </div>

    <div class="disclaimer-box mt-4">
        <strong>Mandatory Decision Support Disclaimer:</strong><br>
        "This analysis is an academic decision-support estimate based on the information provided. It should not be treated as a guaranteed prediction of future academic or employment outcomes."
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

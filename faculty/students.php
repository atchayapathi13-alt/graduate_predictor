<?php
/**
 * Faculty Student Directory with Advanced Filtering
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Student Directory & Filter';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole(['faculty', 'admin']);
$currentUser = getCurrentUser();
$db = getDB();

// Fetch filter options
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll();

// Process Filters
$where = ["1=1"];
$params = [];

$filterDept = $_GET['dept'] ?? '';
if (!empty($filterDept)) {
    $where[] = "d.id = ?";
    $params[] = (int) $filterDept;
}

$filterReadiness = $_GET['readiness'] ?? '';
if (!empty($filterReadiness)) {
    $where[] = "pr.category = ?";
    $params[] = trim($filterReadiness);
}

$filterCgpa = $_GET['cgpa_range'] ?? '';
if ($filterCgpa === '8_plus') {
    $where[] = "ar.current_cgpa >= 8.0";
} elseif ($filterCgpa === '7_to_8') {
    $where[] = "ar.current_cgpa >= 7.0 AND ar.current_cgpa < 8.0";
} elseif ($filterCgpa === 'below_7') {
    $where[] = "ar.current_cgpa < 7.0";
}

$filterAttendance = $_GET['attendance'] ?? '';
if ($filterAttendance === 'shortage') {
    $where[] = "ar.attendance_percentage < 75.0";
} elseif ($filterAttendance === 'normal') {
    $where[] = "ar.attendance_percentage >= 75.0";
}

$filterInternship = $_GET['internship'] ?? '';
if ($filterInternship === 'yes') {
    $where[] = "EXISTS (SELECT 1 FROM internships i WHERE i.student_id = s.id AND i.has_internship = 1)";
} elseif ($filterInternship === 'no') {
    $where[] = "NOT EXISTS (SELECT 1 FROM internships i WHERE i.student_id = s.id AND i.has_internship = 1)";
}

$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $term = "%" . $search . "%";
    $where[] = "(u.name LIKE ? OR s.student_id LIKE ? OR u.email LIKE ?)";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql = "
    SELECT s.id, s.student_id, s.degree, s.academic_year, s.graduation_year,
           u.name, u.email, u.phone, d.dept_code, d.dept_name,
           ar.current_cgpa, ar.attendance_percentage, ar.num_backlogs,
           pa.academic_score, pa.technical_score, pa.career_readiness_score, pa.overall_score,
           pr.category as prediction_category, pr.confidence_score,
           (SELECT count(*) FROM internships i WHERE i.student_id = s.id AND i.has_internship = 1) as internship_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    LEFT JOIN predictions pr ON s.id = pr.student_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY s.id ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Export -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Student Records & Analytical Screening</h4>
        <p class="text-muted small mb-0">Multi-parameter search, filtering, and deep cohort profile inspection</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= baseUrl('faculty/export.php') ?>" class="btn btn-outline-success rounded-pill px-3">
            <i class="fas fa-file-csv me-1"></i> Export Entire Cohort to CSV
        </a>
    </div>
</div>

<!-- Advanced Filter Panel -->
<div class="content-card mb-4">
    <div class="content-card-body p-4">
        <form method="GET" action="<?= baseUrl('faculty/students.php') ?>">
            <div class="row g-3">
                <!-- Search Term -->
                <div class="col-md-4">
                    <label class="form-label small">Search Name / Roll No / Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="e.g. Aarav, STU2024..." value="<?= sanitize($search) ?>">
                    </div>
                </div>

                <!-- Department -->
                <div class="col-md-2">
                    <label class="form-label small">Department</label>
                    <select class="form-select" name="dept">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>>
                                <?= sanitize($d['dept_code']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Readiness Category -->
                <div class="col-md-2">
                    <label class="form-label small">Readiness Outcome</label>
                    <select class="form-select" name="readiness">
                        <option value="">All Categories</option>
                        <option value="High Readiness" <?= $filterReadiness === 'High Readiness' ? 'selected' : '' ?>>High Readiness</option>
                        <option value="Moderate Readiness" <?= $filterReadiness === 'Moderate Readiness' ? 'selected' : '' ?>>Moderate Readiness</option>
                        <option value="Needs Improvement" <?= $filterReadiness === 'Needs Improvement' ? 'selected' : '' ?>>Needs Improvement</option>
                    </select>
                </div>

                <!-- CGPA Range -->
                <div class="col-md-2">
                    <label class="form-label small">CGPA Range</label>
                    <select class="form-select" name="cgpa_range">
                        <option value="">All CGPAs</option>
                        <option value="8_plus" <?= $filterCgpa === '8_plus' ? 'selected' : '' ?>>&ge; 8.00 (Distinction)</option>
                        <option value="7_to_8" <?= $filterCgpa === '7_to_8' ? 'selected' : '' ?>>7.00 - 7.99 (Average)</option>
                        <option value="below_7" <?= $filterCgpa === 'below_7' ? 'selected' : '' ?>>&lt; 7.00 (At Risk)</option>
                    </select>
                </div>

                <!-- Attendance Status -->
                <div class="col-md-2">
                    <label class="form-label small">Attendance Status</label>
                    <select class="form-select" name="attendance">
                        <option value="">All Attendance</option>
                        <option value="normal" <?= $filterAttendance === 'normal' ? 'selected' : '' ?>>&ge; 75% (Eligible)</option>
                        <option value="shortage" <?= $filterAttendance === 'shortage' ? 'selected' : '' ?>>&lt; 75% (Shortage)</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-1 align-items-center">
                <!-- Internship Filter -->
                <div class="col-md-3">
                    <label class="form-label small">Internship Completion</label>
                    <select class="form-select" name="internship">
                        <option value="">All Internship Statuses</option>
                        <option value="yes" <?= $filterInternship === 'yes' ? 'selected' : '' ?>>Completed Internship</option>
                        <option value="no" <?= $filterInternship === 'no' ? 'selected' : '' ?>>No Internship Yet</option>
                    </select>
                </div>

                <div class="col-md-9 text-md-end mt-4">
                    <a href="<?= baseUrl('faculty/students.php') ?>" class="btn btn-light rounded-pill px-3 me-2">
                        <i class="fas fa-rotate-left me-1"></i> Reset Filters
                    </a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="fas fa-filter me-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Student Records Table -->
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Filtered Results (<?= count($students) ?> Students)</h6>
        <span class="badge bg-light text-dark border">Cohort 2023-2027</span>
    </div>
    <div class="content-card-body p-0">
        <?php if (empty($students)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-user-slash fs-1 text-secondary opacity-50 mb-2"></i>
                <h6 class="fw-bold text-dark">No student records match the active filters</h6>
                <p class="small mb-0">Try widening your search terms or resetting the filter parameters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Dept</th>
                            <th>CGPA</th>
                            <th>Attendance</th>
                            <th>Backlogs</th>
                            <th>Internship</th>
                            <th>Overall Score</th>
                            <th>Outcome Tier</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><span class="font-monospace fw-bold text-dark"><?= sanitize($s['student_id']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= sanitize($s['name']) ?></div>
                                    <small class="text-muted"><?= sanitize($s['email']) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= sanitize($s['dept_code']) ?></span></td>
                                <td><strong><?= number_format($s['current_cgpa'], 2) ?></strong></td>
                                <td>
                                    <span class="<?= $s['attendance_percentage'] < 75 ? 'text-danger fw-bold' : 'text-success' ?>">
                                        <?= number_format($s['attendance_percentage'], 1) ?>%
                                    </span>
                                </td>
                                <td>
                                    <?= $s['num_backlogs'] > 0 ? '<span class="badge bg-danger rounded-pill">' . $s['num_backlogs'] . '</span>' : '<span class="text-muted">0</span>' ?>
                                </td>
                                <td>
                                    <?= $s['internship_count'] > 0 ? '<i class="fas fa-check-circle text-success" title="Completed"></i>' : '<i class="fas fa-circle-xmark text-muted" title="Pending"></i>' ?>
                                </td>
                                <td><strong><?= number_format($s['overall_score'], 1) ?>%</strong></td>
                                <td><?= getReadinessBadge($s['prediction_category'] ?? 'Needs Improvement') ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?= baseUrl('faculty/student_detail.php?id=' . $s['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3" title="View Full Analysis">
                                            <i class="fas fa-chart-simple me-1"></i> Analysis
                                        </a>
                                        <a href="<?= baseUrl('student/report.php?student_id=' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2" title="Print Report">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

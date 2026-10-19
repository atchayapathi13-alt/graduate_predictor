<?php
/**
 * At-Risk Students & Academic Support Tracking
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Students Requiring Academic Support';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole(['faculty', 'admin']);
$currentUser = getCurrentUser();
$db = getDB();

// Query students meeting risk criteria
$sql = "
    SELECT s.id, s.student_id, u.name, u.email, u.phone, d.dept_code, d.dept_name,
           ar.current_cgpa, ar.attendance_percentage, ar.num_backlogs,
           pa.academic_score, pa.technical_score, pa.overall_score,
           pr.category as prediction_category, pr.confidence_score
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    LEFT JOIN predictions pr ON s.id = pr.student_id
    WHERE ar.attendance_percentage < 75.0 
       OR ar.current_cgpa < 7.0 
       OR ar.num_backlogs > 0 
       OR pr.category = 'Needs Improvement'
    ORDER BY ar.num_backlogs DESC, ar.attendance_percentage ASC
";

$stmt = $db->query($sql);
$atRiskStudents = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Advice -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-danger"><i class="fas fa-triangle-exclamation me-2"></i>Students Requiring Academic Support</h4>
        <p class="text-muted small mb-0">Identified based on attendance deficits, active uncleared backlogs, or sub-threshold CGPA metrics.</p>
    </div>
    <span class="badge bg-danger text-white rounded-pill px-3 py-2 font-monospace">
        <?= count($atRiskStudents) ?> Flagged Students
    </span>
</div>

<!-- Intervention Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card border-danger-subtle">
            <span class="text-danger small fw-bold text-uppercase d-block mb-1">Attendance Shortage (&lt; 75%)</span>
            <p class="text-muted small mb-0">Risk of detention or condonation fines. Recommend weekly monitoring.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card border-warning-subtle">
            <span class="text-warning text-dark small fw-bold text-uppercase d-block mb-1">Active Backlogs (&gt; 0)</span>
            <p class="text-muted small mb-0">Disqualifies candidates from tier-1 and tier-2 campus placement drives.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card border-primary-subtle">
            <span class="text-primary small fw-bold text-uppercase d-block mb-1">Sub-Threshold CGPA (&lt; 7.00)</span>
            <p class="text-muted small mb-0">Requires internal assessment coaching and assignment remediation.</p>
        </div>
    </div>
</div>

<!-- At-Risk Records Table -->
<div class="content-card">
    <div class="content-card-header bg-danger bg-opacity-10 border-danger-subtle d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-danger mb-0"><i class="fas fa-user-injured me-2"></i>Intervention Roster</h6>
        <span class="badge bg-white text-dark border">Academic Mentorship Priority</span>
    </div>
    <div class="content-card-body p-0">
        <?php if (empty($atRiskStudents)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-circle-check fs-1 text-success mb-2"></i>
                <h6 class="fw-bold text-dark">No students currently flagged for academic risk</h6>
                <p class="small mb-0">All cohort members satisfy minimum attendance, backlog, and CGPA thresholds.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Dept</th>
                            <th>Attendance</th>
                            <th>Active Backlogs</th>
                            <th>Current CGPA</th>
                            <th>Overall Score</th>
                            <th>Risk Factors Flagged</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atRiskStudents as $s): ?>
                            <?php
                                $risks = [];
                                if ($s['attendance_percentage'] < 75.0) {
                                    $risks[] = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Attendance Shortage (' . number_format($s['attendance_percentage'], 1) . '%)</span>';
                                }
                                if ($s['num_backlogs'] > 0) {
                                    $risks[] = '<span class="badge bg-danger text-white">' . $s['num_backlogs'] . ' Backlog(s)</span>';
                                }
                                if ($s['current_cgpa'] < 7.0) {
                                    $risks[] = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Low CGPA (' . number_format($s['current_cgpa'], 2) . ')</span>';
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= sanitize($s['name']) ?></div>
                                    <small class="text-muted font-monospace"><?= sanitize($s['student_id']) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= sanitize($s['dept_code']) ?></span></td>
                                <td>
                                    <span class="fw-bold <?= $s['attendance_percentage'] < 75 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($s['attendance_percentage'], 1) ?>%
                                    </span>
                                </td>
                                <td>
                                    <?= $s['num_backlogs'] > 0 ? '<span class="badge bg-danger rounded-pill">' . $s['num_backlogs'] . ' Active</span>' : '<span class="text-muted">0</span>' ?>
                                </td>
                                <td>
                                    <span class="fw-bold <?= $s['current_cgpa'] < 7.0 ? 'text-danger' : 'text-dark' ?>">
                                        <?= number_format($s['current_cgpa'], 2) ?>
                                    </span>
                                </td>
                                <td><strong><?= number_format($s['overall_score'], 1) ?>%</strong></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?= implode(' ', $risks) ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= baseUrl('faculty/student_detail.php?id=' . $s['id']) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        <i class="fas fa-stethoscope me-1"></i> Mentor Profile
                                    </a>
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

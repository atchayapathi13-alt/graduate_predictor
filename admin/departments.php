<?php
/**
 * Administrator Departments CRUD Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Manage Academic Departments';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole('admin');
$db = getDB();

$error = '';

// Handle Delete Department
if (isset($_GET['delete'])) {
    $deptId = (int) $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM departments WHERE id = ?");
    $stmtDel->execute([$deptId]);
    setFlash('info', 'Department record removed.');
    header("Location: " . baseUrl('admin/departments.php'));
    exit;
}

// Handle Add Department
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired.';
    } else {
        $code = strtoupper(trim($_POST['dept_code'] ?? ''));
        $name = trim($_POST['dept_name'] ?? '');
        $hod  = trim($_POST['hod_name'] ?? '');

        if (empty($code) || empty($name)) {
            $error = 'Department Code and Full Name are required.';
        } else {
            $chk = $db->prepare("SELECT id FROM departments WHERE dept_code = ?");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                $error = 'A department with this Code already exists.';
            } else {
                $stmtIns = $db->prepare("INSERT INTO departments (dept_code, dept_name, hod_name, created_at) VALUES (?, ?, ?, NOW())");
                $stmtIns->execute([$code, $name, $hod]);
                setFlash('success', "Department {$name} ({$code}) created successfully.");
                header("Location: " . baseUrl('admin/departments.php'));
                exit;
            }
        }
    }
}

// Fetch departments with student and faculty counts
$stmt = $db->query("
    SELECT d.*,
           (SELECT count(*) FROM students s WHERE s.department_id = d.id) as student_count,
           (SELECT count(*) FROM faculty f WHERE f.department_id = d.id) as faculty_count
    FROM departments d
    ORDER BY d.id ASC
");
$deptList = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Action Bar -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Academic Departments & Branches</h4>
        <p class="text-muted small mb-0">Manage engineering and technology branches, degree divisions, and department leadership</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#addDeptModal">
        <i class="fas fa-plus me-1"></i> Add Department
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small mb-4"><?= sanitize($error) ?></div>
<?php endif; ?>

<!-- Departments Table -->
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Active Academic Departments (<?= count($deptList) ?>)</h6>
    </div>
    <div class="content-card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Department Title</th>
                        <th>Head of Department (HOD)</th>
                        <th>Students Enrolled</th>
                        <th>Faculty Staff</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deptList as $d): ?>
                        <tr>
                            <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-3 py-1"><?= sanitize($d['dept_code']) ?></span></td>
                            <td><strong class="text-dark"><?= sanitize($d['dept_name']) ?></strong></td>
                            <td class="text-muted"><?= sanitize($d['hod_name'] ?: 'Not Assigned') ?></td>
                            <td><strong><?= (int)$d['student_count'] ?> Students</strong></td>
                            <td><?= (int)$d['faculty_count'] ?> Faculty</td>
                            <td>
                                <a href="<?= baseUrl('admin/departments.php?delete=' . $d['id']) ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 30px; height: 30px;" onclick="return confirm('Delete department <?= sanitize($d['dept_code']) ?>?');" title="Delete">
                                    <i class="fas fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Department -->
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-building-columns me-2 text-primary"></i>Add Academic Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= baseUrl('admin/departments.php') ?>">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label">Department Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="dept_code" required placeholder="e.g. CCE, MECH, CIVIL">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Department Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="dept_name" required placeholder="e.g. Computer and Communication Engineering">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Head of Department (HOD) Name</label>
                        <input type="text" class="form-control" name="hod_name" placeholder="e.g. Dr. K. Radhakrishnan">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * Student & User Registration Page
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helper.php';
require_once __DIR__ . '/classes/AnalysisEngine.php';
require_once __DIR__ . '/classes/PredictionEngine.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'student';
    header("Location: " . baseUrl($role . '/dashboard.php'));
    exit;
}

$db = getDB();
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');
    $role     = trim($_POST['role'] ?? 'student');
    $studentIdCode = strtoupper(trim($_POST['student_id'] ?? ''));
    $deptId   = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $degree   = trim($_POST['degree'] ?? 'B.Tech / B.E');
    $acadYear = trim($_POST['academic_year'] ?? '2023-2027');
    $gradYear = !empty($_POST['graduation_year']) ? (int)$_POST['graduation_year'] : 2027;
    $csrf     = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters in length.';
    } elseif ($role === 'student' && empty($studentIdCode)) {
        $error = 'Please enter a Student ID (e.g., STU2024099).';
    } else {
        // Check for duplicate email
        $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'An account with this email address already exists. Please login instead.';
        } else {
            // If student, check duplicate student_id
            $duplicateCode = false;
            if ($role === 'student') {
                $chkId = $db->prepare("SELECT id FROM students WHERE student_id = ?");
                $chkId->execute([$studentIdCode]);
                if ($chkId->fetch()) {
                    $error = 'This Student ID is already registered.';
                    $duplicateCode = true;
                }
            }

            if (!$duplicateCode) {
                try {
                    $db->beginTransaction();

                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmtU = $db->prepare("
                        INSERT INTO users (name, email, password, role, phone, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmtU->execute([$name, $email, $hashedPassword, $role, $phone]);
                    $newUserId = (int) $db->lastInsertId();

                    if ($role === 'student') {
                        $stmtS = $db->prepare("
                            INSERT INTO students (user_id, student_id, department_id, degree, academic_year, graduation_year, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmtS->execute([$newUserId, $studentIdCode, $deptId, $degree, $acadYear, $gradYear]);
                        $newStudentId = (int) $db->lastInsertId();

                        // Initialize empty baseline academic record
                        $stmtA = $db->prepare("
                            INSERT INTO academic_records (student_id, tenth_percentage, twelfth_percentage, current_cgpa, current_semester, num_backlogs, cleared_backlogs, attendance_percentage)
                            VALUES (?, 75.0, 75.0, 7.0, 1, 0, 0, 80.0)
                        ");
                        $stmtA->execute([$newStudentId]);

                        // Initialize baseline skills
                        $allSkills = $db->query("SELECT id FROM skills")->fetchAll(PDO::FETCH_COLUMN);
                        $stmtSkill = $db->prepare("INSERT INTO student_skills (student_id, skill_id, rating) VALUES (?, ?, 2)");
                        foreach ($allSkills as $sId) {
                            $stmtSkill->execute([$newStudentId, $sId]);
                        }

                        // Run initial analysis & prediction
                        $analysisEngine = new AnalysisEngine();
                        $predEngine = new PredictionEngine();
                        $analysisEngine->calculateStudentAnalysis($newStudentId);
                        $predEngine->predictStudentOutcome($newStudentId);
                    }

                    $db->commit();
                    setFlash('success', 'Registration successful! You can now log in.');
                    header("Location: " . baseUrl('login.php'));
                    exit;

                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Graduate Predicting Analysis System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">

            <div class="text-center mb-4">
                <a href="<?= baseUrl() ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                    <img src="<?= baseUrl('assets/images/logo.svg') ?>" alt="Logo" width="48" height="48">
                    <span class="fs-4 fw-bold text-dark">Graduate Predictor</span>
                </a>
                <p class="text-muted small mt-1">Create your academic profile for outcome readiness modeling</p>
            </div>

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-sm-5">
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 small rounded-3 mb-4" role="alert">
                            <i class="fas fa-circle-exclamation fs-5 flex-shrink-0"></i>
                            <div><?= sanitize($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= baseUrl('register.php') ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="role" value="student">

                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-user me-2 text-primary"></i>Personal Details</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required placeholder="Aarav Sharma" value="<?= sanitize($_POST['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required placeholder="aarav@college.edu" value="<?= sanitize($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required placeholder="Minimum 6 characters">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" placeholder="+91 98765 43210" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 mt-4"><i class="fas fa-graduation-cap me-2 text-primary"></i>Academic Identity</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Student ID / Roll No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="student_id" required placeholder="e.g. STU2024099" value="<?= sanitize($_POST['student_id'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select class="form-select" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                                            <?= sanitize($dept['dept_name']) ?> (<?= sanitize($dept['dept_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Degree</label>
                                <select class="form-select" name="degree">
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="B.E">B.E</option>
                                    <option value="B.Sc CS">B.Sc Computer Science</option>
                                    <option value="BCA">BCA</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Academic Batch</label>
                                <input type="text" class="form-control" name="academic_year" value="2023-2027">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Graduation Year</label>
                                <input type="number" class="form-control" name="graduation_year" value="2027" min="2024" max="2032">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm mb-3">
                            <i class="fas fa-user-plus me-2"></i> Complete Registration
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <span class="text-muted small">Already have an account? </span>
                        <a href="<?= baseUrl('login.php') ?>" class="small fw-bold text-primary text-decoration-none">Sign in here</a>
                    </div>

                </div>
            </div>

            <div class="text-center mt-3">
                <a href="<?= baseUrl() ?>" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Homepage
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

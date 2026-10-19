<?php
/**
 * User Login Page
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helper.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'student';
    header("Location: " . baseUrl($role . '/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $extra = null;
            if ($user['role'] === 'student') {
                $stmtS = $db->prepare("SELECT id, student_id FROM students WHERE user_id = ?");
                $stmtS->execute([$user['id']]);
                $extra = $stmtS->fetch() ?: null;
            } elseif ($user['role'] === 'faculty') {
                $stmtF = $db->prepare("SELECT id, faculty_id FROM faculty WHERE user_id = ?");
                $stmtF->execute([$user['id']]);
                $extra = $stmtF->fetch() ?: null;
            }

            loginUser($user, $extra);
            setFlash('success', "Welcome back, {$user['name']}!");

            // Redirect according to user role
            if ($user['role'] === 'admin') {
                header("Location: " . baseUrl('admin/dashboard.php'));
            } elseif ($user['role'] === 'faculty') {
                header("Location: " . baseUrl('faculty/dashboard.php'));
            } else {
                header("Location: " . baseUrl('student/dashboard.php'));
            }
            exit;
        } else {
            $error = 'Invalid email address or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Graduate Predicting Analysis System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-7 col-xl-5">

            <!-- Logo and Header -->
            <div class="text-center mb-4">
                <a href="<?= baseUrl() ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                    <img src="<?= baseUrl('assets/images/logo.svg') ?>" alt="Logo" width="48" height="48">
                    <span class="fs-4 fw-bold text-dark">Graduate Predictor</span>
                </a>
                <p class="text-muted small mt-1">Sign in to your analytical decision workspace</p>
            </div>

            <!-- Login Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-sm-5">
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 small rounded-3 mb-4" role="alert">
                            <i class="fas fa-circle-exclamation fs-5 flex-shrink-0"></i>
                            <div><?= sanitize($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form id="loginForm" method="POST" action="<?= baseUrl('login.php') ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control border-start-0 ps-0" id="loginEmail" name="email" required placeholder="name@college.edu" value="<?= sanitize($_POST['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="loginPassword" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0" id="loginPassword" name="password" required placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm mb-3">
                            <i class="fas fa-arrow-right-to-bracket me-2"></i> Sign In
                        </button>
                    </form>

                    <!-- Quick 1-Click Demo Logins for Instant Testing -->
                    <div class="pt-3 border-top mt-3">
                        <label class="form-label d-block text-center text-muted small fw-bold text-uppercase mb-2">
                            <i class="fas fa-bolt text-warning me-1"></i> Quick 1-Click Demo Accounts
                        </label>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill btn-demo-login d-flex align-items-center justify-content-between px-3" data-email="student1@graduate.edu" data-role="student">
                                <span><i class="fas fa-user-graduate me-2"></i> Student: Aarav Sharma</span>
                                <span class="badge bg-primary text-white rounded-pill">Demo</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill btn-demo-login d-flex align-items-center justify-content-between px-3" data-email="faculty.cse@graduate.edu" data-role="faculty">
                                <span><i class="fas fa-chalkboard-user me-2"></i> Faculty: Dr. Ramesh Kumar</span>
                                <span class="badge bg-success text-white rounded-pill">Demo</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill btn-demo-login d-flex align-items-center justify-content-between px-3" data-email="admin@graduate.edu" data-role="admin">
                                <span><i class="fas fa-shield-halved me-2"></i> Administrator</span>
                                <span class="badge bg-dark text-white rounded-pill">Demo</span>
                            </button>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <span class="text-muted small">New student without an account? </span>
                        <a href="<?= baseUrl('register.php') ?>" class="small fw-bold text-primary text-decoration-none">Register here</a>
                    </div>

                </div>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-3">
                <a href="<?= baseUrl() ?>" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Homepage
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= baseUrl('assets/js/main.js') ?>"></script>
</body>
</html>

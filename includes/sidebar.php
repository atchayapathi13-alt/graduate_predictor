<?php
/**
 * Navigation Sidebar Component
 * Graduate Predicting Analysis System
 */

$user = getCurrentUser();
$role = $user['role'] ?? 'student';
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside class="app-sidebar">
    <a href="<?= baseUrl() ?>" class="sidebar-brand">
        <img src="<?= baseUrl('assets/images/logo.svg') ?>" alt="Logo" width="34" height="34">
        <span>Graduate Predictor</span>
    </a>

    <ul class="sidebar-menu">
        <?php if ($role === 'student'): ?>
            <li class="sidebar-header">Student Portal</li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/dashboard.php') ?>" class="sidebar-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/profile.php') ?>" class="sidebar-link <?= $currentScript === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> <span>My Profile</span>
                </a>
            </li>
            
            <li class="sidebar-header">Data Entry</li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/academic.php') ?>" class="sidebar-link <?= $currentScript === 'academic.php' ? 'active' : '' ?>">
                    <i class="fas fa-graduation-cap"></i> <span>Academic Info</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/skills.php') ?>" class="sidebar-link <?= $currentScript === 'skills.php' ? 'active' : '' ?>">
                    <i class="fas fa-code"></i> <span>Technical & Soft Skills</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/certifications.php') ?>" class="sidebar-link <?= $currentScript === 'certifications.php' ? 'active' : '' ?>">
                    <i class="fas fa-certificate"></i> <span>Certifications</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/internships.php') ?>" class="sidebar-link <?= $currentScript === 'internships.php' ? 'active' : '' ?>">
                    <i class="fas fa-briefcase"></i> <span>Internship</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/projects.php') ?>" class="sidebar-link <?= $currentScript === 'projects.php' ? 'active' : '' ?>">
                    <i class="fas fa-diagram-project"></i> <span>Projects</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/extracurricular.php') ?>" class="sidebar-link <?= $currentScript === 'extracurricular.php' ? 'active' : '' ?>">
                    <i class="fas fa-award"></i> <span>Extracurricular</span>
                </a>
            </li>

            <li class="sidebar-header">Analytics & Outcome</li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/analysis.php') ?>" class="sidebar-link <?= $currentScript === 'analysis.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> <span>Detailed Analysis</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/prediction.php') ?>" class="sidebar-link <?= $currentScript === 'prediction.php' ? 'active' : '' ?>">
                    <i class="fas fa-bullseye"></i> <span>Outcome Prediction</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/recommendations.php') ?>" class="sidebar-link <?= $currentScript === 'recommendations.php' ? 'active' : '' ?>">
                    <i class="fas fa-lightbulb"></i> <span>Recommendations</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('student/report.php') ?>" class="sidebar-link <?= $currentScript === 'report.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-pdf"></i> <span>Download Report</span>
                </a>
            </li>

        <?php elseif ($role === 'faculty'): ?>
            <li class="sidebar-header">Faculty / Analyst</li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('faculty/dashboard.php') ?>" class="sidebar-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> <span>Overview & Charts</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('faculty/students.php') ?>" class="sidebar-link <?= $currentScript === 'students.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> <span>Student Directory</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('faculty/at_risk.php') ?>" class="sidebar-link <?= $currentScript === 'at_risk.php' ? 'active' : '' ?>">
                    <i class="fas fa-triangle-exclamation text-warning"></i> <span>Students Needing Support</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('faculty/comparison.php') ?>" class="sidebar-link <?= $currentScript === 'comparison.php' ? 'active' : '' ?>">
                    <i class="fas fa-scale-balanced"></i> <span>Student Comparison</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('faculty/export.php') ?>" class="sidebar-link <?= $currentScript === 'export.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-csv"></i> <span>Export Data</span>
                </a>
            </li>

        <?php elseif ($role === 'admin'): ?>
            <li class="sidebar-header">Administrator</li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('admin/dashboard.php') ?>" class="sidebar-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> <span>Admin Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('admin/students.php') ?>" class="sidebar-link <?= $currentScript === 'students.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-graduate"></i> <span>Manage Students</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('admin/faculty.php') ?>" class="sidebar-link <?= $currentScript === 'faculty.php' ? 'active' : '' ?>">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Manage Faculty</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('admin/departments.php') ?>" class="sidebar-link <?= $currentScript === 'departments.php' ? 'active' : '' ?>">
                    <i class="fas fa-building-columns"></i> <span>Departments</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('admin/settings.php') ?>" class="sidebar-link <?= $currentScript === 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-sliders"></i> <span>Scoring Weights</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= baseUrl('admin/analytics.php') ?>" class="sidebar-link <?= $currentScript === 'analytics.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-column"></i> <span>System Analytics</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-weight: 600;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="text-truncate text-white small fw-bold"><?= sanitize($user['name']) ?></div>
                    <div class="text-truncate text-light small opacity-75 text-capitalize"><?= sanitize($user['role']) ?></div>
                </div>
            </div>
            <a href="<?= baseUrl('logout.php') ?>" class="text-light opacity-75 hover-opacity-100 p-2" title="Logout">
                <i class="fas fa-arrow-right-from-bracket"></i>
            </a>
        </div>
    </div>
</aside>
<div class="sidebar-overlay"></div>

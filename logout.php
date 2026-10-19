<?php
/**
 * User Logout
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helper.php';

logoutUser();

// Start a fresh session just to hold the farewell flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
setFlash('info', 'You have been safely signed out. See you soon!');
header("Location: " . baseUrl('login.php'));
exit;

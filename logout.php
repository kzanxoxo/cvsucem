<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (isLoggedIn()) {
    logActivity($pdo, $_SESSION['user_id'], 'logout', 'User logged out');
}
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
redirect(SITE_URL . '/login.php');

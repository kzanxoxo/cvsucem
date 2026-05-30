<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'campus_events');
define('DB_USER', 'root');       
define('DB_PASS', '');           
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'CvSU Events');
define('SITE_SHORT', 'CvSU');
define('UNIVERSITY_NAME', 'Cavite State University');
define('UNIVERSITY_TAGLINE', 'Truth | Excellence | Service');
define('SITE_URL', 'http://localhost/campus-events');
define('CONTACT_EMAIL', 'janrienn.porto@cvsu.edu.ph');
define('ADMIN_EMAIL', 'admin@cvsu.edu.ph');
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');

$pdo = null;

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, 
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

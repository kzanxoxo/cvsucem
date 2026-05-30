<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please login to access the admin panel.');
    redirect(SITE_URL . '/login.php');
}

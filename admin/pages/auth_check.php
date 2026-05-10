<?php
require_once __DIR__ . '/../../api/helpers.php';
session_secure_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /cravedrip/admin/pages/login.php');
    exit;
}

/* Re-generate session ID every 30 minutes to prevent fixation */
if (!isset($_SESSION['_regen_at'])) {
    $_SESSION['_regen_at'] = time();
} elseif (time() - $_SESSION['_regen_at'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_regen_at'] = time();
}

set_security_headers();
csrf_token(); // Ensure token is always initialised for meta output

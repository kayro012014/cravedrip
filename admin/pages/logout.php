<?php
require_once __DIR__ . '/../../api/helpers.php';
session_secure_start();

/* Properly destroy the session */
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
?>
<!DOCTYPE html><html><head></head><body>
<script>
    sessionStorage.removeItem('cravedrip_tab');
    window.location.replace('login.php');
</script>
</body></html>

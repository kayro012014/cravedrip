<?php
require_once __DIR__ . '/../../api/helpers.php';
api_require_auth();
set_security_headers();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

api_require_csrf();

require __DIR__ . '/../../api/config.php';

$action = v_str('action');

if ($action === 'update_name') {
    $name = v_str('name', 100);
    if (strlen($name) < 2) {
        echo json_encode(['ok' => false, 'error' => 'Name must be at least 2 characters.']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
    $stmt->execute([$name, $_SESSION['user_id']]);
    $_SESSION['user_name'] = $name;
    echo json_encode(['ok' => true, 'name' => $name]);

} elseif ($action === 'update_password') {
    $current = $_POST['current'] ?? '';
    $new     = $_POST['new']     ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($new) < 6) {
        echo json_encode(['ok' => false, 'error' => 'New password must be at least 6 characters.']);
        exit;
    }
    if ($new !== $confirm) {
        echo json_encode(['ok' => false, 'error' => 'New passwords do not match.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($current, $user['password_hash'])) {
        echo json_encode(['ok' => false, 'error' => 'Current password is incorrect.']);
        exit;
    }

    $hash = password_hash($new, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([$hash, $_SESSION['user_id']]);
    echo json_encode(['ok' => true]);

} else {
    echo json_encode(['ok' => false, 'error' => 'Invalid action.']);
}

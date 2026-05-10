<?php
require_once __DIR__ . '/../../api/helpers.php';
session_secure_start();
set_security_headers();

/* Redirect already-authenticated users */
if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error   = '';
$blocked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* CSRF verification */
    $posted_token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $error = 'Invalid form submission. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password']      ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } else {
            try {
                require __DIR__ . '/../../api/config.php';
                $ip     = $_SERVER['REMOTE_ADDR'];
                $window = 900; // 15 minutes in seconds
                $max    = 5;

                /* Rate-limit check */
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM login_attempts
                     WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
                );
                $stmt->execute([$ip, $window]);
                if ((int)$stmt->fetchColumn() >= $max) {
                    $blocked = true;
                    $error   = 'Too many failed attempts. Please wait 15 minutes and try again.';
                } else {
                    $stmt = $pdo->prepare(
                        "SELECT id, name, password_hash, role FROM users WHERE username = ? LIMIT 1"
                    );
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password_hash'])) {
                        /* Successful login */
                        $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
                        session_regenerate_id(true);
                        $_SESSION['user_id']    = $user['id'];
                        $_SESSION['user_name']  = $user['name'];
                        $_SESSION['user_role']  = $user['role'];
                        $_SESSION['_regen_at']  = time();
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        echo '<!DOCTYPE html><html><head></head><body>
                            <script>
                                sessionStorage.setItem("cravedrip_tab","1");
                                window.location.replace("../index.php");
                            </script>
                        </body></html>';
                        exit;
                    }

                    /* Failed attempt — log it */
                    $pdo->prepare("INSERT INTO login_attempts (ip) VALUES (?)")->execute([$ip]);
                    $error = 'Invalid username or password.';
                }
            } catch (PDOException $e) {
                /* DB not available yet (before setup) — deny gracefully */
                $error = 'Service unavailable. Please run setup first.';
            }
        }
    }
}

/* Ensure CSRF token exists for the form */
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CraveDrip Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --coffee:   #3d2817;
            --espresso: #1a0f08;
            --latte:    #c4a484;
            --caramel:  #b8763a;
            --cream:    #f5efe6;
            --border:   #e0d5c5;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--espresso);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(184,118,58,0.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 90%, rgba(184,118,58,0.05) 0%, transparent 60%);
            pointer-events: none;
        }

        .login-wrap { width: 100%; max-width: 420px; position: relative; z-index: 1; }

        .login-brand { text-align: center; margin-bottom: 2rem; }

        .login-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            font-weight: 900;
            color: var(--cream);
            letter-spacing: -1px;
            line-height: 1;
        }
        .login-logo span { color: var(--caramel); }

        .login-tagline {
            font-size: 0.68rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(196,164,132,0.55);
            margin-top: 6px;
        }

        .login-card {
            background: #fff;
            border-radius: 18px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 24px 60px rgba(0,0,0,0.45);
        }

        .card-title { font-family: 'Playfair Display', serif; font-size: 1.35rem; color: var(--coffee); font-weight: 700; margin-bottom: 0.3rem; }
        .card-sub   { font-size: 0.8rem; color: var(--latte); margin-bottom: 1.75rem; }

        .login-error {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(231,76,60,0.08);
            border: 1px solid rgba(231,76,60,0.22);
            color: #c0392b;
            border-radius: 9px;
            padding: 0.75rem 1rem;
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
        }

        .form-group { margin-bottom: 1.1rem; }
        .form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--coffee); margin-bottom: 0.35rem; letter-spacing: 0.2px; }

        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: var(--latte); font-size: 0.85rem; pointer-events: none; }
        .input-wrap input {
            width: 100%;
            padding: 0.7rem 0.9rem 0.7rem 2.5rem;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            color: #2a1810;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .input-wrap input:focus { border-color: var(--caramel); box-shadow: 0 0 0 3px rgba(184,118,58,0.12); }
        .input-wrap input::placeholder { color: #c8bdb0; }

        .toggle-pw {
            position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: var(--latte);
            font-size: 0.85rem; padding: 4px; transition: color 0.2s;
        }
        .toggle-pw:hover { color: var(--caramel); }

        .btn-login {
            width: 100%; padding: 0.82rem;
            background: var(--caramel); color: white; border: none;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem; font-weight: 600;
            cursor: pointer; margin-top: 0.5rem;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            box-shadow: 0 4px 14px rgba(184,118,58,0.35);
        }
        .btn-login:hover  { background: #9e5e2a; box-shadow: 0 6px 18px rgba(184,118,58,0.45); }
        .btn-login:active { transform: scale(0.98); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; }

        .login-footer { text-align: center; margin-top: 1.5rem; font-size: 0.72rem; color: rgba(196,164,132,0.5); letter-spacing: 0.3px; }
    </style>
</head>
<body>

<div class="login-wrap">

    <div class="login-brand">
        <div class="login-logo">Crave<span>Drip</span></div>
        <div class="login-tagline">Admin Portal</div>
    </div>

    <div class="login-card">
        <div class="card-title">Welcome back</div>
        <div class="card-sub">Sign in to manage your café</div>

        <?php if ($error !== ''): ?>
        <div class="login-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" id="loginForm">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username"
                           placeholder="Enter your username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autofocus required <?= $blocked ? 'disabled' : '' ?>>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="Enter your password"
                           required <?= $blocked ? 'disabled' : '' ?>>
                    <button type="button" class="toggle-pw" onclick="togglePw()" tabindex="-1">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn" <?= $blocked ? 'disabled' : '' ?>>
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> CraveDrip &mdash; All rights reserved
    </div>

</div>

<script>
function togglePw() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in…';
});
</script>

</body>
</html>

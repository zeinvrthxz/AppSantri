<?php
require_once __DIR__ . '/koneksi.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, password
             FROM admin
             WHERE username = ?
             LIMIT 1'
        );
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $admin &&
            password_verify($password, $admin['password'])
        ) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - AppSantri</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f4f7f5;
        }

        .login-card {
            width: 100%;
            max-width: 410px;
            padding: 32px;
            background: #fff;
            border: 1px solid #e0e8e2;
            border-radius: 16px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .07);
        }

        .login-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            border-radius: 16px;
            background: #e3f3e7;
            font-size: 30px;
        }

        .form-control {
            padding: 11px 12px;
            border-radius: 9px;
        }

        .btn-login {
            padding: 11px;
            border-radius: 9px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-icon">🎓</div>

    <h3 class="text-center fw-bold mb-1">AppSantri</h3>
    <p class="text-center text-secondary mb-4">
        Login Administrator
    </p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="username" class="form-label">
                Username
            </label>
            <input
                type="text"
                name="username"
                id="username"
                class="form-control"
                autocomplete="username"
                required
                autofocus
            >
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">
                Password
            </label>
            <input
                type="password"
                name="password"
                id="password"
                class="form-control"
                autocomplete="current-password"
                required
            >
        </div>

        <button type="submit" class="btn btn-success btn-login w-100">
            Login
        </button>
    </form>

    <p class="text-center text-secondary small mt-4 mb-0">
        &copy; <?= date('Y') ?> AppSantri
    </p>
</div>

</body>
</html>
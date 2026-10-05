<?php
require_once __DIR__ . '/auth.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$product = get_product_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $admin_cfg = get_admin_config();

    if ($username === ($admin_cfg['username'] ?? 'admin') && password_verify($password, $admin_cfg['password_hash'] ?? '')) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= e($product['store_name'] ?? 'Hoodie Store') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0c10;
            --card-bg: #14161d;
            --border-color: #24283b;
            --primary: #ffffff;
            --accent: #25d366;
            --text-main: #f0f2f5;
            --text-muted: #8b92a5;
            --input-bg: #0d0f15;
            --error-bg: #381519;
            --error-border: #f85149;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-image: 
                radial-gradient(at 10% 20%, rgba(37, 211, 102, 0.05) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(255, 255, 255, 0.03) 0px, transparent 50%);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent);
            background: rgba(37, 211, 102, 0.1);
            border: 1px solid rgba(37, 211, 102, 0.2);
            padding: 5px 12px;
            border-radius: 99px;
            margin-bottom: 16px;
            font-weight: 700;
        }
        .brand-badge span {
            width: 6px;
            height: 6px;
            background: var(--accent);
            border-radius: 50%;
            display: inline-block;
        }
        .login-title {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .login-sub {
            font-size: 14px;
            color: var(--text-muted);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #d1d5db;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }
        .form-input {
            width: 100%;
            padding: 13px 16px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            border-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #ffffff;
            color: #0b0c10;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: #f0f0f0;
            transform: translateY(-1px);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .error-box {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: #ff8b8b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .credentials-hint {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px dashed var(--border-color);
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.6;
        }
        .credentials-hint code {
            background: #1c202c;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e5e7eb;
            font-size: 12px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="brand-badge"><span></span> Admin Portal</div>
        <h1 class="login-title"><?= e($product['store_name'] ?? 'THE NORTH HOODIES') ?></h1>
        <p class="login-sub">Manage product details, pricing, and WhatsApp orders</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-input" required autofocus placeholder="admin" value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn-submit">Sign In to Dashboard</button>
    </form>

    <div class="credentials-hint">
        Default Login Credentials:<br>
        Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code><br>
        <span style="opacity: 0.7;">(You can change these anytime in the settings)</span>
    </div>

    <a href="../index.php" class="back-link">← Return to Storefront</a>
</div>

</body>
</html>

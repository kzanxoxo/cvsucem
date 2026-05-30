<?php
require_once dirname(__DIR__) . '/config/database.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$email || $email !== 'admin@cvsu.edu.ph') {
        $message = 'Please enter admin@cvsu.edu.ph';
    } elseif (!$newPassword || strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        try {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@cvsu.edu.ph' AND role = 'admin'");
            $stmt->execute([$hash]);

            if ($stmt->rowCount() > 0) {
                $success = true;
                $message = 'Admin password has been reset successfully. You can now log in.';
            } else {
                $message = 'Admin account not found.';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password | CvSU Events</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        .alert {
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <h1>Reset Admin Password</h1>
        <p class="subtitle">Verify your identity and set a new password</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $success ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@cvsu.edu.ph" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Min. 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                </div>

                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

        <a href="../login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>

<?php

session_start();

// Already logged in? Skip straight to the homepage.
if (isset($_SESSION['user_id'])) {
    header('Location: ../homepage/index.php');
    exit;
}

$status  = $_GET['status'] ?? null;
$message = $_GET['message'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AeroGlide</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .login-card {
            width: 90%;
            max-width: 380px;
            background: #fff;
            padding: 40px 35px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
        }

        h1 {
            font-size: 22px;
            margin-bottom: 20px;
            text-align: center;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 13px;
        }

        button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 20px;
            background: #111;
            color: #fff;
            font-size: 13px;
            cursor: pointer;
        }

        .error {
            background: #fdecea;
            color: #c0392b;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>

    <div class="login-card">

        <h1>Sign in to AeroGlide</h1>

        <?php if ($status === 'error' && $message): ?>
            <p class="error"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="login_function.php">

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Log in</button>

        </form>

    </div>

</body>

</html>
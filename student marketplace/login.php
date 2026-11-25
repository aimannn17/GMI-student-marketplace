<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #0f172a;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            padding: 35px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-sizing: border-box;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .form-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-title h2 {
            margin: 0;
            color: #0f172a;
            font-size: 22px;
            font-weight: 700;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background-color: #ffffff;
            box-sizing: border-box;
            color: #0f172a;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30,64,175,0.1);
        }
        .submit-btn {
            width: 100%;
            background-color: #1e40af;
            color: #ffffff;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background-color 0.2s;
        }
        .submit-btn:hover {
            background-color: #1e3a8a;
        }
        .link-container {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #64748b;
        }
        .link-container a {
            color: #1e40af;
            text-decoration: none;
            font-weight: 600;
        }

      
        .guest-btn {
            display: block;
            width: 100%;
            padding: 11px;
            background-color: #f8fafc;
            color: #475569;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .guest-btn:hover {
            background-color: #f1f5f9;
            border-color: #94a3b8;
            color: #1e293b;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="form-title">
        <h2>Log In</h2>
        <p style="color: #64748b; font-size: 13px; margin: 5px 0 0 0;">GMI Student Marketplace</p>
    </div>

    <form action="index.php" method="post">

        <?php
        $login_errors = [
            '1' => 'Username or password is incorrect. Please try again.',
            '2' => 'This account has been suspended. Please contact an administrator.',
            '3' => 'This account no longer exists.',
        ];
        $err = $_GET['error'] ?? '';
        if (isset($login_errors[$err])) {
            echo '<div style="background-color: #fef2f2; border: 1px solid #fee2e2; color: #ef4444; padding: 10px; border-radius: 8px; font-size: 13px; text-align: center; margin-bottom: 20px;">
                    ⚠️ ' . htmlspecialchars($login_errors[$err]) . '
                  </div>';
        }
        ?>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter your username" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <div style="margin-top: 25px;">
            <input type="submit" name="login" value="Login" class="submit-btn">
        </div>
    </form>

    <div class="link-container">
        Don't have an account? <a href="registrationform.php">Register here</a>
    </div>

   
    <div style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
        <a href="index.php?action=guest_login" class="guest-btn">
            🔍 Browse as Guest
        </a>
        <p style="text-align: center; color: #94a3b8; font-size: 12px; margin: 10px 0 0 0; line-height: 1.5;">
            View and search items without logging in.<br>
            Login required to sell or contact sellers.
        </p>
    </div>
</div>

</body>
</html>
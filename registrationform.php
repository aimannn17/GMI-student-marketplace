<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GMI Marketplace - Register</title>
    <link rel="stylesheet" href="student.css">
</head>
<body>

    <table class="student">
        <thead>
            <tr>
                <th>📝 CREATE AN ACCOUNT</th>
            </tr>
        </thead>
        <tbody>
            <form method="post" action="insertstudent.php">
                <tr>
                    <td>
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" placeholder="Choose a unique username" pattern="[A-Za-z0-9_.]{3,30}" title="3-30 characters: letters, numbers, underscore or dot" maxlength="30" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" maxlength="100" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" placeholder="At least 6 characters" minlength="6" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" minlength="6" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="phone_number">Phone Number:</label>
                        <input type="text" id="phone_number" name="phone_number" placeholder="e.g., 0188856335" pattern="[0-9+\-\s]{7,20}" title="Digits only, e.g. 0123456789" maxlength="20" required>
                    </td>
                </tr>
                
                <tr>
                    <td style="padding-top: 20px;">
                        <input type="submit" name="submit" value="Register Now">
                    </td>
                </tr>
            </form>
            <tr>
                <td style="text-align: center; padding-bottom: 30px;">
                    <p style="color: #64748b; font-size: 14px;">Already registered? <a href="login.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Login here</a></p>
                </td>
            </tr>
        </tbody>
    </table>

</body>
</html>
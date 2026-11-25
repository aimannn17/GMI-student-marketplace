<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';


if (!isset($_SESSION['userid'])) {
    js_alert_redirect('Please log in first!', 'index.php');
}

$user_id = (int)$_SESSION['userid'];
$error_msg = "";
$success_msg = "";


if (isset($_POST['update_profile'])) {
    $raw_fullname = trim($_POST['fullname'] ?? '');
    $raw_username = trim($_POST['username'] ?? '');
    $raw_phone    = trim($_POST['phone_number'] ?? '');
    $password     = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error_msg = "Your session expired. Please try again.";
    } elseif ($raw_fullname === '' || $raw_username === '' || $raw_phone === '') {
        $error_msg = "Please fill in all required fields!";
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $raw_username)) {
        $error_msg = "Username must be 3-30 characters: letters, numbers, underscore or dot.";
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $raw_phone)) {
        $error_msg = "Please enter a valid phone number.";
    } elseif ($password !== '' && strlen($password) < 6) {
        $error_msg = "New password must be at least 6 characters.";
    } else {
        $fullname     = mysqli_real_escape_string($dataconn, $raw_fullname);
        $username     = mysqli_real_escape_string($dataconn, $raw_username);
        $phone_number = mysqli_real_escape_string($dataconn, $raw_phone);

        if ($password !== '') {
            $hashed_password = md5($password);
            $query_update = "UPDATE user SET fullname='$fullname', username='$username', phone_number='$phone_number', password='$hashed_password' WHERE user_id=$user_id";
        } else {
            $query_update = "UPDATE user SET fullname='$fullname', username='$username', phone_number='$phone_number' WHERE user_id=$user_id";
        }

        if (mysqli_query($dataconn, $query_update)) {
            $success_msg = "Profile updated successfully!";
            $_SESSION['fullname'] = $raw_fullname;
            $_SESSION['name']     = $raw_username;
        } elseif (mysqli_errno($dataconn) == 1062) {
            $error_msg = "That username is already taken. Please choose another one.";
        } else {
            error_log('profile.php update failed: ' . mysqli_error($dataconn));
            $error_msg = "Could not update your profile. Please try again.";
        }
    }
}


$query_user  = "SELECT * FROM user WHERE user_id = $user_id";
$result_user = mysqli_query($dataconn, $query_user);
$user_data   = mysqli_fetch_assoc($result_user);

$user_phone = isset($user_data['phone_number']) ? $user_data['phone_number'] : '';
$user_role  = isset($user_data['role']) ? $user_data['role'] : 1;
?>

<div class="profile-container" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #eef2f5; font-family: 'Segoe UI', sans-serif;">
    
    <div style="text-align: center; margin-bottom: 25px;">
        <div style="width: 80px; height: 80px; background-color: #eff6ff; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; font-size: 32px; font-weight: bold;">
            <?php echo h(strtoupper(substr($user_data['username'], 0, 1))); ?>
        </div>
        <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 700;">Profile Settings</h2>
        
        <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;"><?php $role_names = [1 => 'Member', 2 => 'Seller', 3 => 'Admin']; echo h($role_names[$user_role] ?? 'Member'); ?></p>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div style="background-color: #dcfce7; color: #15803d; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 600; text-align: center;">
            ✅ <?php echo h($success_msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background-color: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 600; text-align: center;">
            ⚠️ <?php echo h($error_msg); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=7">
        <?php echo csrf_field(); ?>
        <div style="margin-bottom: 18px;">
            <label style="display: block; margin-bottom: 8px; color: #334155; font-weight: 600; font-size: 14px;">Full Name:</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_data['fullname']); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;" required>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; margin-bottom: 8px; color: #334155; font-weight: 600; font-size: 14px;">Username:</label>
            <input type="text" name="username" maxlength="30" value="<?php echo htmlspecialchars($user_data['username']); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;" required>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; margin-bottom: 8px; color: #334155; font-weight: 600; font-size: 14px;">Phone Number:</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user_phone); ?>" placeholder="Example: 0123456789" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;" required>
        </div>

        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; color: #334155; font-weight: 600; font-size: 14px;">New Password:</label>
            <input type="password" name="password" placeholder="Leave blank if you don't want to change the password" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            <small style="color: #94a3b8; font-size: 12px; display: block; margin-top: 4px;">*Enter new password only if you want to change it.</small>
        </div>

        <button type="submit" name="update_profile" style="width: 100%; background-color: #3b82f6; color: #ffffff; padding: 12px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
            💾 Save Profile Changes
        </button>
    </form>
</div>
<?php
// Admin: edit a user's details. Opened from the dashboard via index.php?page=10&user_id=ID
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

// If someone opens this file directly, send them through index.php so the normal layout loads.
if (!defined('IN_APP')) {
    header('Location: index.php?page=10&user_id=' . (int)($_GET['user_id'] ?? 0));
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 3) {
    js_alert_redirect('Access Denied!', 'index.php');
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
    js_alert_redirect('Invalid ID!', 'index.php?page=8&tab=users');
}

$error_msg = '';

if (isset($_POST['update_btn'])) {
    $raw_username = trim($_POST['username'] ?? '');
    $raw_fullname = trim($_POST['fullname'] ?? '');
    $raw_phone    = trim($_POST['phone_number'] ?? '');

    if (!csrf_valid()) {
        $error_msg = 'Your session expired. Please try again.';
    } elseif ($raw_username === '' || $raw_fullname === '' || $raw_phone === '') {
        $error_msg = 'All fields are required.';
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $raw_username)) {
        $error_msg = 'Username must be 3-30 characters: letters, numbers, underscore or dot.';
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $raw_phone)) {
        $error_msg = 'Please enter a valid phone number.';
    } else {
        $username = mysqli_real_escape_string($dataconn, $raw_username);
        $fullname = mysqli_real_escape_string($dataconn, $raw_fullname);
        $phone    = mysqli_real_escape_string($dataconn, $raw_phone);

        $update_sql = "UPDATE user SET username='$username', fullname='$fullname', phone_number='$phone'
                       WHERE user_id=$user_id AND role != 3";

        if (mysqli_query($dataconn, $update_sql)) {
            js_alert_redirect('User updated successfully!', 'index.php?page=8&tab=users');
        } elseif (mysqli_errno($dataconn) == 1062) {
            $error_msg = 'That username is already taken.';
        } else {
            error_log('edit_user.php update failed: ' . mysqli_error($dataconn));
            $error_msg = 'Update failed. Please try again.';
        }
    }
}

$query = mysqli_query($dataconn, "SELECT * FROM user WHERE user_id=$user_id AND role != 3");
$user  = $query ? mysqli_fetch_assoc($query) : null;

if (!$user) {
    js_alert_redirect('User not found (admin accounts cannot be edited here).', 'index.php?page=8&tab=users');
}

// Keep what the admin typed if validation failed
$val_username = isset($_POST['update_btn']) ? ($raw_username ?? '') : $user['username'];
$val_fullname = isset($_POST['update_btn']) ? ($raw_fullname ?? '') : $user['fullname'];
$val_phone    = isset($_POST['update_btn']) ? ($raw_phone ?? '')    : $user['phone_number'];
?>

<div style="max-width: 500px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: 'Segoe UI', sans-serif;">
    <h2>Edit User</h2>

    <?php if ($error_msg !== ''): ?>
        <div style="background-color: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 15px; font-weight: 500;">
            <?php echo h($error_msg); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=10&user_id=<?php echo $user_id; ?>">
        <?php echo csrf_field(); ?>
        <label>Username:</label><br>
        <input type="text" name="username" maxlength="30" value="<?php echo h($val_username); ?>" required style="width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box;">

        <label>Full Name:</label><br>
        <input type="text" name="fullname" maxlength="100" value="<?php echo h($val_fullname); ?>" required style="width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box;">

        <label>Phone Number:</label><br>
        <input type="text" name="phone_number" maxlength="20" value="<?php echo h($val_phone); ?>" required style="width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box;">

        <button type="submit" name="update_btn" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Update User</button>
        <a href="index.php?page=8&tab=users" style="margin-left: 10px; color: #64748b;">Cancel</a>
    </form>
</div>

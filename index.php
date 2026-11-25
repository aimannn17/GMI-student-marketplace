<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';
define('IN_APP', true);   // lets included pages know they are running inside index.php

/** Redirect with JS (output may already have started). */
function go($url) {
    echo '<script>window.location.href=' . json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP) . ';</script>';
    exit();
}

/** Write one row to login_log (silently ignored if the table is missing). */
function log_login($dataconn, $user_id, $username, $status) {
    $ip   = mysqli_real_escape_string($dataconn, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $user = mysqli_real_escape_string($dataconn, $username);
    $uid  = ($user_id === null) ? 'NULL' : (int)$user_id;
    $st   = mysqli_real_escape_string($dataconn, $status);
    mysqli_query($dataconn, "INSERT INTO login_log (user_id, username, ip_address, status)
                              VALUES ($uid, '$user', '$ip', '$st')");
}

// ── Guest mode ───────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'guest_login') {
    $_SESSION['guest'] = true;
    unset($_SESSION['userid']);
    go('index.php?page=1');
}

if (isset($_GET['action']) && $_GET['action'] === 'guest_logout') {
    session_destroy();
    go('login.php');
}

// ── Login ────────────────────────────────────────────────────
if (isset($_POST['login'])) {
    $raw_username = $_POST['username'] ?? '';
    $username = mysqli_real_escape_string($dataconn, $raw_username);
    $password = mysqli_real_escape_string($dataconn, $_POST['password'] ?? '');

    $query  = "SELECT * FROM user WHERE username='$username' AND password=MD5('$password')";
    $result = mysqli_query($dataconn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if (($row['status'] ?? 'active') === 'suspended') {
            log_login($dataconn, $row['user_id'], $raw_username, 'suspended');
            go('login.php?error=2');
        }

        session_regenerate_id(true);
        $_SESSION['userid']   = $row['user_id'];
        $_SESSION['id']       = $row['user_id'];
        $_SESSION['name']     = $row['username'];
        $_SESSION['role']     = $row['role'];
        $_SESSION['fullname'] = $row['fullname'];
        unset($_SESSION['guest']);

        log_login($dataconn, $row['user_id'], $raw_username, 'success');
        go('index.php?page=1');
    } else {
        log_login($dataconn, null, $raw_username, 'failed');
        go('login.php?error=1');
    }
}

// ── Keep sessions honest: suspended / deleted users are kicked out,
//    and role changes made by the admin take effect immediately ──
if (isset($_SESSION['userid'])) {
    $uid_check = (int)$_SESSION['userid'];
    $chk = mysqli_query($dataconn, "SELECT role, status FROM user WHERE user_id = $uid_check");
    if ($chk) {
        $u = mysqli_fetch_assoc($chk);
        if (!$u || ($u['status'] ?? 'active') === 'suspended') {
            $why = $u ? 2 : 3;
            session_unset();
            session_destroy();
            go('login.php?error=' . $why);
        }
        $_SESSION['role'] = $u['role'];
    }
}

$is_logged_in = isset($_SESSION['userid']);
$is_guest     = !$is_logged_in && isset($_SESSION['guest']) && $_SESSION['guest'] === true;
$has_access   = $is_logged_in || $is_guest;

// Pages guests are not allowed to open
$protected_pages = [3, 4, 5, 6, 7, 8, 9, 10];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GMI Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Modal Overlay untuk redirect guest ── */
        .guest-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }
        .guest-modal-overlay.active {
            display: flex;
        }
        .guest-modal {
            background: #ffffff;
            border-radius: 16px;
            padding: 36px 32px;
            max-width: 380px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .guest-modal .modal-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }
        .guest-modal h3 {
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        .guest-modal p {
            color: #64748b;
            font-size: 14px;
            margin: 0 0 24px 0;
            line-height: 1.6;
        }
        .guest-modal .modal-buttons {
            display: flex;
            gap: 10px;
        }
        .guest-modal .btn-login {
            flex: 1;
            background: #1e40af;
            color: white;
            padding: 11px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .guest-modal .btn-register {
            flex: 1;
            background: #f1f5f9;
            color: #1e293b;
            padding: 11px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .guest-modal .btn-cancel {
            display: block;
            margin-top: 14px;
            color: #94a3b8;
            font-size: 13px;
            cursor: pointer;
            background: none;
            border: none;
            font-family: inherit;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php if (!$has_access): ?>
   
    <?php go('login.php'); ?>

<?php else: ?>

    <?php include 'header.php'; ?>

   
    <div class="guest-modal-overlay" id="guestModal">
        <div class="guest-modal">
            <div class="modal-icon">🔒</div>
            <h3>Login Required</h3>
            <p id="guestModalMsg">You need to login or register to perform this action.</p>
            <div class="modal-buttons">
                <a href="login.php" class="btn-login">Login</a>
                <a href="registrationform.php" class="btn-register">Register</a>
            </div>
            <button class="btn-cancel" onclick="closeGuestModal()">Maybe later</button>
        </div>
    </div>

    <div class="content-area">
        <?php
      
        if ($is_guest && in_array($page, $protected_pages)) {
            go('index.php?page=1&show_modal=1');
        }

        switch ($page) {
            case 1:  include 'home.php';            break;
            case 2:  include 'view_item.php';       break;
            case 3:  include 'upload_item.php';     break;
            case 4:  include 'order_summary.php';   break;
            case 5:  include 'edit_item.php';       break;
            case 6:  include 'checkout_sales.php';  break;
            case 7:  include 'profile.php';         break;
            case 8:  include 'admin_dashboard.php'; break;
            case 9:  include 'delete_item.php';     break;
            case 10: include 'edit_user.php';       break;
            default: include 'home.php';            break;
        }
        ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    
        <?php if (isset($_GET['show_modal'])): ?>
        document.getElementById('guestModal').classList.add('active');
        <?php endif; ?>

     
        function showGuestModal(message) {
            if (message) {
                document.getElementById('guestModalMsg').textContent = message;
            }
            document.getElementById('guestModal').classList.add('active');
        }

        function closeGuestModal() {
            document.getElementById('guestModal').classList.remove('active');
        }

   
        document.getElementById('guestModal').addEventListener('click', function(e) {
            if (e.target === this) closeGuestModal();
        });
    </script>

<?php endif; ?>

</body>
</html>
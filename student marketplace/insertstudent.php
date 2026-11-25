<?php
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    js_alert_redirect('Please fill in the registration form.', 'registrationform.php');
}

$username     = trim($_POST["username"] ?? '');
$fullname     = trim($_POST["fullname"] ?? '');
$password     = $_POST["password"] ?? '';
$confirm      = $_POST["confirm_password"] ?? '';
$phone_number = trim($_POST["phone_number"] ?? '');

if ($username === "" || $fullname === "" || $password === "" || $phone_number === "") {
    js_alert_back('All fields are required!');
}
if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
    js_alert_back('Username must be 3-30 characters: letters, numbers, underscore or dot.');
}
if (strlen($fullname) > 100) {
    js_alert_back('Full name is too long (max 100 characters).');
}
if (strlen($password) < 6) {
    js_alert_back('Password must be at least 6 characters.');
}
if ($password !== $confirm) {
    js_alert_back('Passwords do not match.');
}
if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone_number)) {
    js_alert_back('Please enter a valid phone number.');
}

$e_username = mysqli_real_escape_string($dataconn, $username);
$e_fullname = mysqli_real_escape_string($dataconn, $fullname);
$e_phone    = mysqli_real_escape_string($dataconn, $phone_number);
$e_hash     = md5($password);   // matches the MD5() check in index.php (see README for upgrade advice)
$role       = 1;                // everyone registers as a normal member; admin is created in the database only

// Friendly duplicate check
$dup = mysqli_query($dataconn, "SELECT user_id FROM user WHERE username = '$e_username'");
if ($dup && mysqli_num_rows($dup) > 0) {
    js_alert_back('That username is already taken. Please choose another one.');
}

$sql = "INSERT INTO user (username, fullname, password, phone_number, role)
        VALUES ('$e_username', '$e_fullname', '$e_hash', '$e_phone', $role)";

if (mysqli_query($dataconn, $sql)) {
    js_alert_redirect('Account registered successfully! You can now login.', 'login.php');
} else {
    // 1062 = duplicate key (two people registered the same username at the same moment)
    if (mysqli_errno($dataconn) == 1062) {
        js_alert_back('That username is already taken. Please choose another one.');
    }
    error_log('insertstudent.php failed: ' . mysqli_error($dataconn));
    js_alert_back('Registration failed. Please try again.');
}
?>

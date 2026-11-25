<?php
session_start();
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['userid'])) {
    js_alert_redirect('Please login to sell items.', 'login.php');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_item'])) {
    js_alert_redirect('Invalid request.', 'index.php?page=3');
}
if (($_SESSION['role'] ?? 1) == 3) {
    js_alert_redirect('Admin accounts cannot sell items.', 'index.php?page=8');
}
if (!csrf_valid()) {
    js_alert_redirect('Your session expired. Please try again.', 'index.php?page=3');
}

$user_id      = (int)$_SESSION['userid'];
$item_name    = trim($_POST['item_name'] ?? '');
$price_raw    = trim($_POST['price'] ?? '');
$description  = trim($_POST['description'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$category_id  = (int)($_POST['category_id'] ?? 0);
$condition_id = (int)($_POST['condition_id'] ?? 0);

// ── Validation ──
if ($item_name === '' || strlen($item_name) > 150) {
    js_alert_back('Item name is required (max 150 characters).');
}
if (!is_numeric($price_raw) || (float)$price_raw <= 0 || (float)$price_raw > 99999999) {
    js_alert_back('Please enter a valid price greater than 0.');
}
if ($description === '') {
    js_alert_back('Description is required.');
}
if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
    js_alert_back('Please enter a valid phone number.');
}
if ($category_id <= 0 || $condition_id <= 0) {
    js_alert_back('Please choose a category and condition.');
}

// ── Image upload (type-checked, random file name) ──
$upload_error = '';
$filename = save_uploaded_image($_FILES['myfile'] ?? null, $upload_error);
if ($filename === null) {
    js_alert_back($upload_error);
}

$price = number_format((float)$price_raw, 2, '.', '');
$e_name  = mysqli_real_escape_string($dataconn, $item_name);
$e_desc  = mysqli_real_escape_string($dataconn, $description);
$e_phone = mysqli_real_escape_string($dataconn, $phone);
$e_file  = mysqli_real_escape_string($dataconn, $filename);

$sql = "INSERT INTO item (item_name, price, description, phone_number, user_id, image, category_id, condition_id, status)
        VALUES ('$e_name', '$price', '$e_desc', '$e_phone', $user_id, '$e_file', $category_id, $condition_id, 'active')";

if (mysqli_query($dataconn, $sql)) {
    js_alert_redirect('Item successfully added!', 'index.php?page=1');
} else {
    error_log('action_up.php insert failed: ' . mysqli_error($dataconn));
    @unlink('image/' . $filename);
    js_alert_back('Could not save the item. Please try again.');
}
?>

<?php
// Standalone admin endpoint (the dashboard handles most actions in-page).
// Kept for "add item on behalf of a user" - now secured:
// admin-only, CSRF-checked, integer ids, type-checked image upload stored in image/.
session_start();
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 3) {
    js_alert_redirect('Access Denied!', 'index.php');
}
if (!csrf_valid()) {
    js_alert_redirect('Your session expired. Please try again.', 'index.php?page=8');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ── DELETE USER (also removes their listings) ──
if ($action == 'delete_user') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    mysqli_query($dataconn, "DELETE FROM item WHERE user_id = $user_id AND user_id IN (SELECT user_id FROM user WHERE role != 3)");
    mysqli_query($dataconn, "DELETE FROM user WHERE user_id = $user_id AND role != 3");
    header("Location: index.php?page=8&tab=users&msg=user_deleted");
    exit();
}

// ── SUSPEND USER ──
if ($action == 'suspend_user') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    mysqli_query($dataconn, "UPDATE user SET status = 'suspended' WHERE user_id = $user_id AND role != 3");
    header("Location: index.php?page=8&tab=users&msg=user_suspended");
    exit();
}

// ── ACTIVATE USER ──
if ($action == 'activate_user') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    mysqli_query($dataconn, "UPDATE user SET status = 'active' WHERE user_id = $user_id");
    header("Location: index.php?page=8&tab=users&msg=user_activated");
    exit();
}

// ── DELETE ITEM ──
if ($action == 'delete_item') {
    $item_id = (int)($_GET['item_id'] ?? 0);
    $res = mysqli_query($dataconn, "SELECT image FROM item WHERE item_id = $item_id");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_query($dataconn, "DELETE FROM item WHERE item_id = $item_id");
    if ($row) { delete_image_if_unused($dataconn, $row['image']); }
    header("Location: index.php?page=8&tab=items&msg=item_deleted");
    exit();
}

// ── EDIT ITEM (POST) ──
if ($action == 'edit_item' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id   = (int)($_POST['item_id'] ?? 0);
    $raw_name  = trim($_POST['item_name'] ?? '');
    $raw_price = trim($_POST['price'] ?? '');
    if ($raw_name === '' || !is_numeric($raw_price) || (float)$raw_price <= 0) {
        js_alert_redirect('Please enter a valid item name and price.', 'index.php?page=8&tab=items');
    }
    $item_name = mysqli_real_escape_string($dataconn, $raw_name);
    $price     = number_format((float)$raw_price, 2, '.', '');
    mysqli_query($dataconn, "UPDATE item SET item_name = '$item_name', price = '$price' WHERE item_id = $item_id");
    header("Location: index.php?page=8&tab=items&msg=item_updated");
    exit();
}

// ── ADD ITEM ON BEHALF OF A USER (POST) ──
if ($action == 'add_item' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $raw_name    = trim($_POST['item_name'] ?? '');
    $raw_price   = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $user_id     = (int)($_POST['user_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition_id= (int)($_POST['condition_id'] ?? 0);

    if ($raw_name === '' || !is_numeric($raw_price) || (float)$raw_price <= 0 || $description === '' || $user_id <= 0) {
        js_alert_redirect('Please fill in all item details correctly.', 'index.php?page=8&tab=items');
    }

    $upload_error = '';
    $filename = save_uploaded_image($_FILES['myfile'] ?? null, $upload_error);
    if ($filename === null) {
        js_alert_redirect($upload_error, 'index.php?page=8&tab=items');
    }

    $item_name = mysqli_real_escape_string($dataconn, $raw_name);
    $price     = number_format((float)$raw_price, 2, '.', '');
    $e_desc    = mysqli_real_escape_string($dataconn, $description);
    $e_phone   = mysqli_real_escape_string($dataconn, $phone);
    $e_file    = mysqli_real_escape_string($dataconn, $filename);

    mysqli_query($dataconn, "INSERT INTO item (item_name, price, description, phone_number, user_id, image, category_id, condition_id, status)
                              VALUES ('$item_name', '$price', '$e_desc', '$e_phone', $user_id, '$e_file', $category_id, $condition_id, 'active')");
    header("Location: index.php?page=8&tab=items&msg=item_added");
    exit();
}

// Fallback
header("Location: index.php?page=8");
exit();
?>

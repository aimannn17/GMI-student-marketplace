<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['userid'])) {
    js_alert_redirect('Please login first!', 'index.php');
}

$user_role = $_SESSION['role'] ?? 1;
$user_id   = (int)$_SESSION['userid'];
$item_id   = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($item_id <= 0) {
    js_alert_redirect('Item ID not specified!', 'index.php?page=1');
}

// Deleting changes data, so the link must carry the CSRF token
if (!csrf_valid()) {
    js_alert_redirect('Your session expired. Please open the item and try again.', 'index.php?page=2&item_id=' . $item_id);
}

// Owner or admin only
if ($user_role == 3) {
    $query_check = "SELECT * FROM item WHERE item_id = $item_id";
} else {
    $query_check = "SELECT * FROM item WHERE item_id = $item_id AND user_id = $user_id";
}
$result_check = mysqli_query($dataconn, $query_check);

if (!$result_check || mysqli_num_rows($result_check) == 0) {
    js_alert_redirect('Item not found or you do not have permission to delete this item!', 'index.php?page=1');
}

$item_data  = mysqli_fetch_assoc($result_check);
$image_name = $item_data['image'];

$query_delete = "DELETE FROM item WHERE item_id = $item_id";
if ($user_role != 3) {
    $query_delete .= " AND user_id = $user_id";
}

if (mysqli_query($dataconn, $query_delete)) {
    delete_image_if_unused($dataconn, $image_name);
    js_alert_redirect('Item deleted successfully!', 'index.php?page=1');
} else {
    error_log('delete_item.php failed: ' . mysqli_error($dataconn));
    js_alert_redirect('Could not delete the item. Please try again.', 'index.php?page=2&item_id=' . $item_id);
}
?>

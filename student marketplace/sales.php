<?php
// Seller records a sale made offline to a named buyer.
// Writes to the same `transactions` table the checkout uses, so the sale shows up in
// Order History and in the admin dashboard. (No page links here yet - POST a form with
// item_id, buyer_username, final_price, payment_method, delivery_method, csrf and submit_sale.)
session_start();
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['userid']) || !isset($_POST['submit_sale'])) {
    js_alert_redirect('Invalid Access or Request!', 'index.php?page=4');
}
if (!csrf_valid()) {
    js_alert_redirect('Your session expired. Please try again.', 'index.php?page=4');
}

$seller_id      = (int)$_SESSION['userid'];
$item_id        = (int)($_POST['item_id'] ?? 0);
$buyer_username = mysqli_real_escape_string($dataconn, trim($_POST['buyer_username'] ?? ''));
$price_raw      = trim($_POST['final_price'] ?? '');
$payment_method = mysqli_real_escape_string($dataconn, $_POST['payment_method'] ?? '');
$delivery_method= mysqli_real_escape_string($dataconn, $_POST['delivery_method'] ?? '');
$date_sold      = date('Y-m-d H:i:s');

if (!is_numeric($price_raw) || (float)$price_raw <= 0) {
    js_alert_back('Please enter a valid price.');
}
$final_price = number_format((float)$price_raw, 2, '.', '');

$result_buyer = mysqli_query($dataconn, "SELECT user_id FROM user WHERE username = '$buyer_username'");
if (!$result_buyer || mysqli_num_rows($result_buyer) == 0) {
    js_alert_back('Error: Buyer username does not exist! Please check the spelling.');
}
$buyer_id = (int)mysqli_fetch_assoc($result_buyer)['user_id'];

if ($buyer_id === $seller_id) {
    js_alert_back('You cannot sell an item to yourself.');
}

mysqli_begin_transaction($dataconn);

// Seller must own the item and it must still be on sale
$res  = mysqli_query($dataconn, "SELECT item_id FROM item WHERE item_id = $item_id AND user_id = $seller_id AND status = 'active' FOR UPDATE");
if (!$res || mysqli_num_rows($res) == 0) {
    mysqli_rollback($dataconn);
    js_alert_back('Item not found, not yours, or already sold.');
}

$ok = mysqli_query($dataconn,
    "INSERT INTO transactions (item_id, buyer_id, seller_id, price, status, transaction_date, payment_method, delivery_method)
     VALUES ($item_id, $buyer_id, $seller_id, '$final_price', 'Completed', '$date_sold', '$payment_method', '$delivery_method')")
   && mysqli_query($dataconn, "UPDATE item SET status = 'sold' WHERE item_id = $item_id AND user_id = $seller_id");

if ($ok) {
    mysqli_commit($dataconn);
    js_alert_redirect('Success! The sale has been recorded to @' . $_POST['buyer_username'] . '.', 'index.php?page=4');
}

error_log('sales.php failed: ' . mysqli_error($dataconn));
mysqli_rollback($dataconn);
js_alert_back('Could not record the sale. Please try again.');
?>

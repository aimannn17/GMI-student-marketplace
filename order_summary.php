<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['userid'])) {
    echo "<div style='text-align:center; padding:50px;'>⚠️ Please login to view your order history.</div>";
    return;
}

$current_user_id = (int)$_SESSION['userid'];

// LEFT JOINs + the price stored on the transaction keep old orders visible
// even if the item or the other user has since been deleted.
$result_purchase = mysqli_query($dataconn,
    "SELECT t.*, i.item_name, seller.fullname AS partner_name
     FROM transactions t
     LEFT JOIN item i ON t.item_id = i.item_id
     LEFT JOIN user seller ON t.seller_id = seller.user_id
     WHERE t.buyer_id = $current_user_id
     ORDER BY t.transaction_date DESC");

$result_sales = mysqli_query($dataconn,
    "SELECT t.*, i.item_name, buyer.fullname AS partner_name
     FROM transactions t
     LEFT JOIN item i ON t.item_id = i.item_id
     LEFT JOIN user buyer ON t.buyer_id = buyer.user_id
     WHERE t.seller_id = $current_user_id
     ORDER BY t.transaction_date DESC");

/** Print table rows for a transaction result set. */
if (!function_exists('render_order_rows')) {
    function render_order_rows($result) {
        if (!$result || mysqli_num_rows($result) == 0) {
            echo '<tr><td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">No records yet.</td></tr>';
            return;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td style="padding:8px;">' . h($row['item_name'] ?? '[Item removed]') . '</td>';
            echo '<td style="padding:8px;">' . h($row['partner_name'] ?? '[Deleted user]') . '</td>';
            echo '<td style="padding:8px;">RM ' . number_format($row['price'], 2) . '</td>';
            echo '<td style="padding:8px;">' . h($row['transaction_date']) . '</td>';
            echo '<td style="padding:8px;">' . h($row['payment_method']) . '</td>';
            echo '<td style="padding:8px;">' . h($row['delivery_method']) . '</td>';
            echo '<td style="padding:8px;">' . h($row['status']) . '</td>';
            echo '</tr>';
        }
    }
}
?>

<div style="max-width: 1000px; margin: 30px auto; font-family: sans-serif; overflow-x: auto;">

    <h2 style="color: #1e293b; border-left: 5px solid #3b82f6; padding-left: 10px;">📦 Purchase History</h2>
    <table border="1" style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
        <tr style="background: #eef2ff;">
            <th>Item</th><th>Seller</th><th>Price</th><th>Date</th><th>Payment Method</th><th>Delivery Location</th><th>Status</th>
        </tr>
        <?php render_order_rows($result_purchase); ?>
    </table>

    <h2 style="color: #1e293b; border-left: 5px solid #10b981; padding-left: 10px;">💰 Sales History</h2>
    <table border="1" style="width: 100%; border-collapse: collapse;">
        <tr style="background: #ecfdf5;">
            <th>Item</th><th>Buyer</th><th>Price</th><th>Date</th><th>Payment Method</th><th>Delivery Location</th><th>Status</th>
        </tr>
        <?php render_order_rows($result_sales); ?>
    </table>
</div>

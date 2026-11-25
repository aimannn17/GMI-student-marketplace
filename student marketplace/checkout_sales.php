<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['userid'])) {
    js_alert_redirect('⚠️ Please login first!', 'index.php');
}
if (($_SESSION['role'] ?? 1) == 3) {
    js_alert_redirect('Admin accounts cannot make purchases.', 'index.php?page=8');
}

$buyer_id = (int)$_SESSION['userid'];

$allowed_payment  = ['Cash', 'Online Transfer'];
$allowed_delivery = ['Hostel', 'Cafeteria'];

// ── Place the order ──────────────────────────────────────────
if (isset($_POST['submit_order'])) {
    if (!csrf_valid()) {
        js_alert_redirect('Your session expired. Please try again.', 'index.php?page=1');
    }

    $item_id         = (int)($_POST['item_id'] ?? 0);
    $payment_method  = $_POST['payment_method']  ?? '';
    $delivery_method = $_POST['delivery_method'] ?? '';

    if (!in_array($payment_method, $allowed_payment, true) || !in_array($delivery_method, $allowed_delivery, true)) {
        js_alert_redirect('Please choose a valid payment and delivery option.', 'index.php?page=6&item_id=' . $item_id);
    }

    // Lock the row so two buyers cannot purchase the same item at the same time.
    mysqli_begin_transaction($dataconn);

    $res  = mysqli_query($dataconn, "SELECT item_id, user_id, price, status FROM item WHERE item_id = $item_id FOR UPDATE");
    $item = $res ? mysqli_fetch_assoc($res) : null;

    if (!$item) {
        mysqli_rollback($dataconn);
        js_alert_redirect('This item no longer exists.', 'index.php?page=1');
    }
    if (($item['status'] ?? 'active') !== 'active') {
        mysqli_rollback($dataconn);
        js_alert_redirect('Sorry, this item has already been sold.', 'index.php?page=1');
    }
    if ((int)$item['user_id'] === $buyer_id) {
        mysqli_rollback($dataconn);
        js_alert_redirect('You cannot buy your own item.', 'index.php?page=2&item_id=' . $item_id);
    }

    // Seller and price always come from the database, never from the browser.
    $seller_id        = (int)$item['user_id'];
    $price            = $item['price'];
    $transaction_date = date('Y-m-d H:i:s');
    $e_pay            = mysqli_real_escape_string($dataconn, $payment_method);
    $e_del            = mysqli_real_escape_string($dataconn, $delivery_method);

    $ok = mysqli_query($dataconn,
        "INSERT INTO transactions (item_id, buyer_id, seller_id, price, status, transaction_date, payment_method, delivery_method)
         VALUES ($item_id, $buyer_id, $seller_id, '$price', 'Completed', '$transaction_date', '$e_pay', '$e_del')");

    $ok = $ok && mysqli_query($dataconn, "UPDATE item SET status = 'sold' WHERE item_id = $item_id AND status = 'active'")
              && mysqli_affected_rows($dataconn) === 1;

    if ($ok) {
        mysqli_commit($dataconn);
        js_alert_redirect('✅ Purchase successful!', 'index.php?page=4');
    }

    error_log('checkout_sales.php failed: ' . mysqli_error($dataconn));
    mysqli_rollback($dataconn);
    js_alert_redirect('Purchase failed. Please try again.', 'index.php?page=2&item_id=' . $item_id);
}

// ── Show the checkout form ───────────────────────────────────
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$res     = mysqli_query($dataconn, "SELECT * FROM item WHERE item_id = $item_id");
$item    = $res ? mysqli_fetch_assoc($res) : null;

if (!$item) {
    js_alert_redirect('Item not found.', 'index.php?page=1');
}
if (($item['status'] ?? 'active') !== 'active') {
    js_alert_redirect('Sorry, this item has already been sold.', 'index.php?page=1');
}
if ((int)$item['user_id'] === $buyer_id) {
    js_alert_redirect('You cannot buy your own item.', 'index.php?page=2&item_id=' . $item_id);
}
?>

<div style="max-width: 500px; margin: 30px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2>Checkout Item</h2>
    <form action="index.php?page=6" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">

        <p><strong>Item:</strong> <?php echo h($item['item_name']); ?></p>
        <p><strong>Price:</strong> RM <?php echo number_format($item['price'], 2); ?></p>

        <label>Payment Method:</label>
        <select name="payment_method" required style="width: 100%; padding: 10px; margin-bottom: 10px;">
            <?php foreach ($allowed_payment as $opt): ?>
                <option value="<?php echo h($opt); ?>"><?php echo h($opt); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Delivery Location:</label>
        <select name="delivery_method" required style="width: 100%; padding: 10px; margin-bottom: 20px;">
            <?php foreach ($allowed_delivery as $opt): ?>
                <option value="<?php echo h($opt); ?>"><?php echo h($opt); ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="submit_order" style="width: 100%; padding: 10px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">Confirm Checkout</button>
        <p style="text-align:center; margin-top:12px;"><a href="index.php?page=2&item_id=<?php echo (int)$item['item_id']; ?>" style="color:#64748b; text-decoration:none; font-size:14px;">Cancel</a></p>
    </form>
</div>

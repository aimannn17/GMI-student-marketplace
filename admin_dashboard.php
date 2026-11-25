<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 3) {
    js_alert_redirect('Access Denied!', 'index.php');
}

// Every state-changing action below needs a valid CSRF token
$admin_action_requested = isset($_GET['suspend_user']) || isset($_GET['activate_user'])
                       || isset($_GET['delete_user'])  || isset($_GET['delete_item'])
                       || isset($_POST['edit_item_submit']);
if ($admin_action_requested && !csrf_valid()) {
    js_alert_redirect('Your session expired. Please try again.', 'index.php?page=8');
}


if (isset($_GET['suspend_user'])) {
    $uid = (int)$_GET['suspend_user'];
    mysqli_query($dataconn, "UPDATE user SET status='suspended' WHERE user_id='$uid' AND role != 3");
    echo "<script>alert('User has been suspended.'); window.location.href='index.php?page=8&tab=users';</script>"; exit();
}

if (isset($_GET['activate_user'])) {
    $uid = (int)$_GET['activate_user'];
    mysqli_query($dataconn, "UPDATE user SET status='active' WHERE user_id='$uid'");
    echo "<script>alert('User has been activated.'); window.location.href='index.php?page=8&tab=users';</script>"; exit();
}

if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    // Remove the user's listings too, otherwise they would stay on the home page pointing at a missing seller
    mysqli_query($dataconn, "DELETE FROM item WHERE user_id = $uid AND user_id IN (SELECT user_id FROM user WHERE role != 3)");
    mysqli_query($dataconn, "DELETE FROM user WHERE user_id = $uid AND role != 3");
    echo "<script>alert('User has been deleted.'); window.location.href='index.php?page=8&tab=users';</script>"; exit();
}

if (isset($_GET['delete_item'])) {
    $iid = (int)$_GET['delete_item'];
    $img_res = mysqli_query($dataconn, "SELECT image FROM item WHERE item_id = $iid");
    $img_row = $img_res ? mysqli_fetch_assoc($img_res) : null;
    mysqli_query($dataconn, "DELETE FROM item WHERE item_id = $iid");
    if ($img_row) { delete_image_if_unused($dataconn, $img_row['image']); }
    echo "<script>alert('Item has been deleted.'); window.location.href='index.php?page=8&tab=items';</script>"; exit();
}

if (isset($_POST['edit_item_submit'])) {
    $iid       = (int)$_POST['edit_item_id'];
    $raw_name  = trim($_POST['edit_item_name'] ?? '');
    $raw_price = trim($_POST['edit_item_price'] ?? '');
    if ($raw_name === '' || !is_numeric($raw_price) || (float)$raw_price <= 0) {
        js_alert_redirect('Please enter a valid item name and price.', 'index.php?page=8&tab=items');
    }
    $iname  = mysqli_real_escape_string($dataconn, $raw_name);
    $iprice = number_format((float)$raw_price, 2, '.', '');
    mysqli_query($dataconn, "UPDATE item SET item_name='$iname', price='$iprice' WHERE item_id=$iid");
    echo "<script>alert('Item has been updated.'); window.location.href='index.php?page=8&tab=items';</script>"; exit();
}

// ──────────────────────────────────────────────
// STATS & TAB
// ──────────────────────────────────────────────
$tab    = isset($_GET['tab']) ? $_GET['tab'] : 'items';
$tab    = in_array($tab, ['items', 'orders', 'users'], true) ? $tab : 'items';
$search_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = mysqli_real_escape_string($dataconn, $search_raw);

$total_users = mysqli_fetch_assoc(mysqli_query($dataconn, "SELECT COUNT(*) AS c FROM user WHERE role != 3"))['c'];
$total_items = mysqli_fetch_assoc(mysqli_query($dataconn, "SELECT COUNT(*) AS c FROM item"))['c'];
$total_sales = mysqli_fetch_assoc(mysqli_query($dataconn, "SELECT COUNT(*) AS c FROM transactions"))['c'];
?>

<div style="font-family:'Segoe UI',sans-serif; padding:24px; background:#f8fafc; min-height:100vh;">

    <h2 style="color:#1e293b; margin-bottom:20px;">📊 Admin Dashboard</h2>

    <!-- STATS CARDS -->
    <div style="display:flex; gap:16px; margin-bottom:28px; flex-wrap:wrap;">
        <?php
        $stats = [
            ['👤 Total Users',        $total_users, '#3b82f6'],
            ['📦 Total Items',        $total_items, '#10b981'],
            ['🧾 Total Transactions', $total_sales, '#f59e0b'],
        ];
        foreach ($stats as $s): ?>
        <div style="background:#fff; border-radius:10px; padding:18px 28px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border-left:4px solid <?php echo $s[2]; ?>; flex:1; min-width:180px;">
            <div style="font-size:13px; color:#64748b;"><?php echo $s[0]; ?></div>
            <div style="font-size:28px; font-weight:700; color:#1e293b;"><?php echo $s[1]; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- TAB NAV -->
    <?php
    $tabs = [
        'items'  => '📦 All Items',
        'orders' => '🧾 Purchase History',
        'users'  => '👤 Manage Users',
    ];
    ?>
    <div style="display:flex; gap:8px; margin-bottom:24px; border-bottom:2px solid #e2e8f0;">
        <?php foreach ($tabs as $key => $label): ?>
        <a href="index.php?page=8&tab=<?php echo $key; ?>" style="
            padding:10px 20px; border-radius:8px 8px 0 0; text-decoration:none;
            font-weight:600; font-size:14px;
            background:<?php echo $tab==$key ? '#3b82f6' : '#fff'; ?>;
            color:<?php echo $tab==$key ? '#fff' : '#475569'; ?>;
            border:1px solid <?php echo $tab==$key ? '#3b82f6' : '#e2e8f0'; ?>;
        "><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>


    <?php if ($tab == 'items'): ?>

    <form method="GET" action="index.php" style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
        <input type="hidden" name="page" value="8">
        <input type="hidden" name="tab" value="items">
        <input type="text" name="search" value="<?php echo h($search_raw); ?>" placeholder="Search item name or seller..." style="padding:9px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; width:300px;">
        <button type="submit" style="background:#3b82f6; color:#fff; border:none; padding:9px 20px; border-radius:8px; font-weight:600; cursor:pointer;">🔍 Search</button>
        <?php if ($search): ?>
        <a href="index.php?page=8&tab=items" style="padding:9px 16px; background:#f1f5f9; color:#475569; border-radius:8px; text-decoration:none; font-weight:600;">✕ Clear</a>
        <?php endif; ?>
    </form>

    <?php
    $where = $search ? "WHERE (item.item_name LIKE '%$search%' OR user.fullname LIKE '%$search%')" : "";
    $q_items = mysqli_query($dataconn,
        "SELECT item.*, user.fullname AS seller_name
         FROM item
         LEFT JOIN user ON item.user_id = user.user_id
         $where
         ORDER BY item.item_id DESC"
    );
    ?>

    <!-- Edit Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:30px; border-radius:12px; width:420px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <h3 style="margin-top:0; color:#1e293b;">✏️ Edit Item</h3>
            <form method="POST" action="index.php?page=8&tab=items">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="edit_item_id" id="modal_item_id">
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px; font-weight:600; color:#334155; display:block; margin-bottom:5px;">Item Name</label>
                    <input type="text" name="edit_item_name" id="modal_item_name" required style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="font-size:13px; font-weight:600; color:#334155; display:block; margin-bottom:5px;">Price (RM)</label>
                    <input type="number" step="0.01" min="0.01" name="edit_item_price" id="modal_item_price" required style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; box-sizing:border-box;">
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" name="edit_item_submit" style="background:#3b82f6; color:#fff; border:none; padding:10px 0; border-radius:8px; font-weight:600; cursor:pointer; flex:1;">💾 Save Changes</button>
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'" style="background:#f1f5f9; color:#475569; border:none; padding:10px 0; border-radius:8px; font-weight:600; cursor:pointer; flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openEdit(btn) {
        document.getElementById('modal_item_id').value = btn.dataset.id;
        document.getElementById('modal_item_name').value = btn.dataset.name;
        document.getElementById('modal_item_price').value = btn.dataset.price;
        document.getElementById('editModal').style.display = 'flex';
    }
    </script>

    <div style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="background:#f1f5f9; color:#475569; text-align:left;">
                    <th style="padding:12px 16px;">ID</th>
                    <th style="padding:12px 16px;">Item Name</th>
                    <th style="padding:12px 16px;">Seller</th>
                    <th style="padding:12px 16px;">Price</th>
                    <th style="padding:12px 16px;">Status</th>
                    <th style="padding:12px 16px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($q_items && mysqli_num_rows($q_items) > 0):
                while ($row = mysqli_fetch_assoc($q_items)): ?>
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:12px 16px; color:#94a3b8;">#<?php echo $row['item_id']; ?></td>
                    <td style="padding:12px 16px; font-weight:600; color:#1e293b;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td style="padding:12px 16px; color:#475569;"><?php echo htmlspecialchars($row['seller_name'] ?? 'N/A'); ?></td>
                    <td style="padding:12px 16px; color:#10b981; font-weight:600;">RM <?php echo number_format($row['price'], 2); ?></td>
                    <td style="padding:12px 16px;">
                        <?php $is_item_sold = (($row['status'] ?? 'active') === 'sold'); ?>
                        <span style="background:<?php echo $is_item_sold ? '#e2e8f0' : '#dcfce7'; ?>; color:<?php echo $is_item_sold ? '#475569' : '#16a34a'; ?>; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;"><?php echo h(ucfirst($row['status'] ?? 'active')); ?></span>
                    </td>
                    <td style="padding:12px 16px;">
                        <div style="display:flex; gap:6px;">
                            <button type="button" onclick="openEdit(this)"
                                data-id="<?php echo (int)$row['item_id']; ?>"
                                data-name="<?php echo h($row['item_name']); ?>"
                                data-price="<?php echo h($row['price']); ?>"
                                style="background:#3b82f6; color:#fff; border:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">✏️ Edit</button>
                            <a href="index.php?page=8&tab=items&delete_item=<?php echo (int)$row['item_id']; ?>&<?php echo csrf_query(); ?>"
                                data-msg="<?php echo h("Delete item '" . $row['item_name'] . "'?"); ?>"
                                onclick="return confirm(this.dataset.msg)"
                                style="background:#ef4444; color:#fff; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none;">🗑️ Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr><td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">No items found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($tab == 'orders'): ?>

    <?php
    $q_orders = mysqli_query($dataconn,
        "SELECT t.*,
                i.item_name,
                buyer.fullname  AS buyer_name,  buyer.username  AS buyer_username,
                seller.fullname AS seller_name, seller.username AS seller_username
         FROM transactions t
         LEFT JOIN item i       ON t.item_id   = i.item_id
         LEFT JOIN user buyer   ON t.buyer_id  = buyer.user_id
         LEFT JOIN user seller  ON t.seller_id = seller.user_id
         ORDER BY t.transaction_date DESC"
    );
    ?>

    <div style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="background:#f1f5f9; color:#475569; text-align:left;">
                    <th style="padding:12px 16px;">#</th>
                    <th style="padding:12px 16px;">Item</th>
                    <th style="padding:12px 16px;">Buyer</th>
                    <th style="padding:12px 16px;">Seller</th>
                    <th style="padding:12px 16px;">Price</th>
                    <th style="padding:12px 16px;">Payment</th>
                    <th style="padding:12px 16px;">Delivery</th>
                    <th style="padding:12px 16px;">Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($q_orders && mysqli_num_rows($q_orders) > 0):
                while ($row = mysqli_fetch_assoc($q_orders)): ?>
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:12px 16px; color:#94a3b8;"><?php echo (int)$row['transaction_id']; ?></td>
                    <td style="padding:12px 16px; font-weight:600; color:#1e293b;"><?php echo h($row['item_name'] ?? '[Item removed]'); ?></td>
                    <td style="padding:12px 16px;">
                        <span style="color:#1e293b; font-weight:600;"><?php echo h($row['buyer_name'] ?? '[Deleted user]'); ?></span><br>
                        <span style="font-size:11px; color:#94a3b8;">@<?php echo h($row['buyer_username'] ?? '-'); ?></span>
                    </td>
                    <td style="padding:12px 16px;">
                        <span style="color:#1e293b; font-weight:600;"><?php echo h($row['seller_name'] ?? '[Deleted user]'); ?></span><br>
                        <span style="font-size:11px; color:#94a3b8;">@<?php echo h($row['seller_username'] ?? '-'); ?></span>
                    </td>
                    <td style="padding:12px 16px; color:#10b981; font-weight:600;">RM <?php echo number_format($row['price'], 2); ?></td>
                    <td style="padding:12px 16px; color:#475569;"><?php echo htmlspecialchars($row['payment_method']); ?></td>
                    <td style="padding:12px 16px; color:#475569;"><?php echo htmlspecialchars($row['delivery_method']); ?></td>
                    <td style="padding:12px 16px; color:#64748b; font-size:13px;"><?php echo h($row['transaction_date']); ?></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr><td colspan="8" style="text-align:center; padding:40px; color:#94a3b8;">No transactions found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

  
    <?php elseif ($tab == 'users'): ?>

    <form method="GET" action="index.php" style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
        <input type="hidden" name="page" value="8">
        <input type="hidden" name="tab" value="users">
        <input type="text" name="search" value="<?php echo h($search_raw); ?>" placeholder="Search username or name..." style="padding:9px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; width:300px;">
        <button type="submit" style="background:#3b82f6; color:#fff; border:none; padding:9px 20px; border-radius:8px; font-weight:600; cursor:pointer;">🔍 Search</button>
        <?php if ($search): ?>
        <a href="index.php?page=8&tab=users" style="padding:9px 16px; background:#f1f5f9; color:#475569; border-radius:8px; text-decoration:none; font-weight:600;">✕ Clear</a>
        <?php endif; ?>
    </form>

    <?php
    $user_where = "WHERE role != 3";
    if ($search) $user_where .= " AND (username LIKE '%$search%' OR fullname LIKE '%$search%')";
    $q_users = mysqli_query($dataconn, "SELECT * FROM user $user_where ORDER BY user_id ASC");
    $role_label = [1 => 'Member', 2 => 'Seller', 3 => 'Admin'];
    ?>

    <div style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="background:#f1f5f9; color:#475569; text-align:left;">
                    <th style="padding:12px 16px;">ID</th>
                    <th style="padding:12px 16px;">Username</th>
                    <th style="padding:12px 16px;">Full Name</th>
                    <th style="padding:12px 16px;">Phone</th>
                    <th style="padding:12px 16px;">Role</th>
                    <th style="padding:12px 16px;">Status</th>
                    <th style="padding:12px 16px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($q_users && mysqli_num_rows($q_users) > 0):
                while ($row = mysqli_fetch_assoc($q_users)):
                    $suspended = (($row['status'] ?? 'active') == 'suspended');
            ?>
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:12px 16px; color:#94a3b8;">#<?php echo $row['user_id']; ?></td>
                    <td style="padding:12px 16px; font-weight:600; color:#1e293b;">@<?php echo htmlspecialchars($row['username']); ?></td>
                    <td style="padding:12px 16px; color:#475569;"><?php echo htmlspecialchars($row['fullname']); ?></td>
                    <td style="padding:12px 16px; color:#475569;"><?php echo htmlspecialchars($row['phone_number']); ?></td>
                    <td style="padding:12px 16px;">
                        <span style="background:#eff6ff; color:#3b82f6; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">
                            <?php echo h($role_label[$row['role']] ?? 'User'); ?>
                        </span>
                    </td>
                    <td style="padding:12px 16px;">
                        <?php if ($suspended): ?>
                        <span style="background:#fee2e2; color:#ef4444; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Suspended</span>
                        <?php else: ?>
                        <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Active</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 16px;">
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <a href="index.php?page=10&user_id=<?php echo (int)$row['user_id']; ?>"
                               style="background:#3b82f6; color:#fff; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none;">✏️ Edit</a>
                            <?php if ($suspended): ?>
                            <a href="index.php?page=8&tab=users&activate_user=<?php echo (int)$row['user_id']; ?>&<?php echo csrf_query(); ?>"
                               data-msg="<?php echo h('Re-activate @' . $row['username'] . '?'); ?>"
                               onclick="return confirm(this.dataset.msg)"
                               style="background:#10b981; color:#fff; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none;">✅ Activate</a>
                            <?php else: ?>
                            <a href="index.php?page=8&tab=users&suspend_user=<?php echo (int)$row['user_id']; ?>&<?php echo csrf_query(); ?>"
                               data-msg="<?php echo h('Suspend @' . $row['username'] . '?'); ?>"
                               onclick="return confirm(this.dataset.msg)"
                               style="background:#f59e0b; color:#fff; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none;">⏸ Suspend</a>
                            <?php endif; ?>
                            <a href="index.php?page=8&tab=users&delete_user=<?php echo (int)$row['user_id']; ?>&<?php echo csrf_query(); ?>"
                               data-msg="<?php echo h('Delete account @' . $row['username'] . ' and all of their listings? This action cannot be undone.'); ?>"
                               onclick="return confirm(this.dataset.msg)"
                               style="background:#ef4444; color:#fff; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none;">🗑️ Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile;
            else: ?>
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

</div>
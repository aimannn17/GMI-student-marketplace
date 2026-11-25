<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($item_id <= 0) {
    echo "<div style='text-align:center; padding:50px; color:#ef4444; font-weight:600;'>⚠️ Error: Item ID not found!</div>";
    return;
}

$query_view = "SELECT item.*,
                      user.username AS seller_username,
                      user.fullname AS seller_fullname,
                      categories.category_name,
                      `condition`.condition_name
               FROM item
               INNER JOIN user ON item.user_id = user.user_id
               LEFT JOIN categories ON item.category_id = categories.category_id
               LEFT JOIN `condition` ON item.condition_id = `condition`.condition_id
               WHERE item.item_id = $item_id";

$result_view = mysqli_query($dataconn, $query_view);

if (!$result_view || mysqli_num_rows($result_view) == 0) {
    echo "<div style='text-align:center; padding:50px; color:#ef4444; font-weight:600;'>⚠️ This item does not exist or has been deleted.</div>";
    return;
}

$row = mysqli_fetch_assoc($result_view);

$product_id     = (int)$row['item_id'];
$product_name   = $row['item_name'];
$product_price  = $row['price'];
$description    = $row['description'];
$image          = $row['image'];
$phone          = $row['phone_number'];
$seller_name    = $row['seller_fullname'];
$category_name  = $row['category_name']  ?? 'N/A';
$condition_name = $row['condition_name'] ?? 'N/A';
$is_sold        = (($row['status'] ?? 'active') === 'sold');

$logged_in = isset($_SESSION['userid']);
$my_role   = $_SESSION['role'] ?? 1;
$is_owner  = $logged_in && ((int)$_SESSION['userid'] === (int)$row['user_id']);
?>

<div style="max-width: 800px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px; position: relative;">
            <?php
            $img_src = (!empty($image) && file_exists("image/" . $image))
                        ? "image/" . rawurlencode($image)
                        : null;
            ?>
            <?php if ($img_src): ?>
                <img src="<?php echo h($img_src); ?>" alt="<?php echo h($product_name); ?>" style="width: 100%; border-radius: 8px; border: 1px solid #eee;">
            <?php else: ?>
                <div style="width:100%; height:300px; background:#f1f5f9; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:14px; font-weight:600; border: 1px solid #e2e8f0;">
                    No Image Available
                </div>
            <?php endif; ?>
            <?php if ($is_sold): ?>
                <span style="position:absolute; top:12px; left:12px; background:#ef4444; color:#fff; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:700;">SOLD</span>
            <?php endif; ?>
        </div>

        <div style="flex: 1; min-width: 300px;">
            <h1 style="margin-top: 0; color: #1e293b;"><?php echo h($product_name); ?></h1>
            <h2 style="color: #10b981;">RM <?php echo number_format($product_price, 2); ?></h2>
            <p style="color: #64748b; line-height: 1.6;"><?php echo nl2br(h($description)); ?></p>

            <p><strong>Category:</strong> <?php echo h($category_name); ?></p>
            <p><strong>Condition:</strong> <?php echo h(ucfirst($condition_name)); ?></p>
            <p><strong>Seller:</strong> <?php echo h($seller_name); ?></p>

            <?php if ($logged_in): ?>
                <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; margin-top: 10px;">
                    📞 <strong>Contact:</strong> <?php echo h($phone); ?>
                </div>
            <?php else: ?>
                <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; margin-top: 10px; color: #64748b;">
                    🔒 <strong>Contact:</strong> <a href="login.php">Login</a> to see the seller's phone number.
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <?php
                if ($logged_in) {
                    if ($my_role == 3) {
                        echo '<div style="background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 8px; text-align: center; border: 1px solid #fecaca;">
                                ⚠️ Admin cannot make purchases.
                              </div>';
                    } elseif ($is_owner) {
                        ?>
                        <div style="display: flex; gap: 10px;">
                            <a href="index.php?page=5&item_id=<?php echo $product_id; ?>" style="flex: 1; text-align: center; background-color: #3b82f6; color: #ffffff; padding: 12px; border-radius: 8px; font-size: 16px; font-weight: 700; text-decoration: none;">
                                ✏️ Edit Item
                            </a>
                            <a href="index.php?page=9&item_id=<?php echo $product_id; ?>&<?php echo csrf_query(); ?>" onclick="return confirm('Are you sure you want to delete this item?');" style="flex: 1; text-align: center; background-color: #ef4444; color: #ffffff; padding: 12px; border-radius: 8px; font-size: 16px; font-weight: 700; text-decoration: none;">
                                🗑️ Delete Item
                            </a>
                        </div>
                        <?php
                    } elseif ($is_sold) {
                        echo '<div style="background-color: #f1f5f9; color: #64748b; padding: 14px; border-radius: 8px; text-align: center; font-weight: 700;">
                                This item has been sold.
                              </div>';
                    } else {
                        ?>
                        <a href="index.php?page=6&item_id=<?php echo $product_id; ?>" style="display: block; text-align: center; background-color: #e67e22; color: #ffffff; padding: 14px; border-radius: 8px; font-size: 16px; font-weight: 700; text-decoration: none;">
                            🛒 Buy Now / Checkout
                        </a>
                        <?php
                    }
                } else {
                    echo '<div style="background-color: #eff6ff; color: #1e40af; padding: 12px; border-radius: 8px; text-align: center; border: 1px solid #bfdbfe;">
                            Please <a href="login.php">login</a> to purchase or manage this item.
                          </div>';
                }
                ?>
            </div>

            <p style="margin-top: 20px;"><a href="index.php?page=1" style="color:#64748b; text-decoration:none; font-size:14px;">&larr; Back to marketplace</a></p>
        </div>
    </div>
</div>

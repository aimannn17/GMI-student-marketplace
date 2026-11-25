<?php
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

$is_logged_in = isset($_SESSION['userid']);
$is_guest     = !$is_logged_in && isset($_SESSION['guest']) && $_SESSION['guest'] === true;

// Filters (all sanitised: category is an integer, sort is whitelisted)
$cat  = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = (isset($_GET['sort']) && in_array($_GET['sort'], ['murah', 'mahal'], true)) ? $_GET['sort'] : '';

// Search now uses GET so it survives changing category / sort (POST still accepted for old links)
$search_raw = '';
if (isset($_GET['search'])) {
    $search_raw = trim($_GET['search']);
} elseif (isset($_POST['search_box'])) {
    $search_raw = trim($_POST['search_box']);
}
$search_keyword = mysqli_real_escape_string($dataconn, $search_raw);

$query = "SELECT * FROM item WHERE status = 'active'";

if ($search_raw !== '') {
    $query .= " AND item_name LIKE '%$search_keyword%'";
}
if ($cat > 0) {
    $query .= " AND category_id = $cat";
}
if ($sort === 'murah') {
    $query .= " ORDER BY price ASC";
} elseif ($sort === 'mahal') {
    $query .= " ORDER BY price DESC";
} else {
    $query .= " ORDER BY item_id DESC";
}

$result_item = mysqli_query($dataconn, $query);
$result_cats = mysqli_query($dataconn, "SELECT category_id, category_name FROM categories ORDER BY category_id");
?>

<div style="font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;">

    <?php if ($is_guest): ?>
    <div style="
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        color: white;
        padding: 14px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 14px;
    ">
        <span>
            👋 You're browsing as a <strong>Guest</strong> — 
            You can view and search items, but login is needed to sell or contact sellers.
        </span>
        <div style="display: flex; gap: 10px;">
            <a href="login.php" style="
                background: white;
                color: #1e40af;
                padding: 7px 16px;
                border-radius: 6px;
                text-decoration: none;
                font-weight: 700;
                font-size: 13px;
                white-space: nowrap;
            ">Login</a>
            <a href="registrationform.php" style="
                background: rgba(255,255,255,0.15);
                color: white;
                padding: 7px 16px;
                border-radius: 6px;
                text-decoration: none;
                font-weight: 600;
                font-size: 13px;
                border: 1px solid rgba(255,255,255,0.3);
                white-space: nowrap;
            ">Register</a>
        </div>
    </div>
    <?php endif; ?>


    <form method="GET" action="index.php">
    <input type="hidden" name="page" value="1">

    <div style="margin: 20px; padding: 15px; background: #f4f4f4; border-radius: 8px; display: flex; gap: 10px; flex-wrap: wrap;">
        <select name="category" onchange="this.form.submit()"
            style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
            <option value="">All Categories</option>
            <?php if ($result_cats): while ($c = mysqli_fetch_assoc($result_cats)): ?>
                <option value="<?php echo (int)$c['category_id']; ?>" <?php if ($cat == $c['category_id']) echo 'selected'; ?>>
                    <?php echo h($c['category_name']); ?>
                </option>
            <?php endwhile; endif; ?>
        </select>

        <select name="sort" onchange="this.form.submit()"
            style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
            <option value="">Sort Price</option>
            <option value="murah" <?php if ($sort == 'murah') echo 'selected'; ?>>Lowest Price</option>
            <option value="mahal" <?php if ($sort == 'mahal') echo 'selected'; ?>>Highest Price</option>
        </select>
    </div>

    <h2 style="text-align: center; margin-top: 10px; margin-bottom: 20px; color: #2c3e50; font-weight: 700;">
        GMI Student Marketplace
    </h2>

  
    <div style="display: flex; justify-content: center; margin-bottom: 30px;">
        <div style="width: 100%; max-width: 600px; display: flex; gap: 10px; padding: 0 20px; box-sizing: border-box;">
            <input type="text"
                   name="search"
                   placeholder="Find items..."
                   value="<?php echo h($search_raw); ?>"
                   style="flex-grow: 1; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; font-family: inherit;">
            <button type="submit"
                    style="background-color: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-family: inherit;">
                Search
            </button>
        </div>
    </div>

    </form>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; padding: 10px 20px 30px;">
        <?php
        if ($result_item && mysqli_num_rows($result_item) > 0) {
            while ($row = mysqli_fetch_assoc($result_item)) {
                $product_name  = isset($row['item_name']) ? $row['item_name'] : 'No Name';
                $product_price = isset($row['price'])     ? $row['price']     : 0.00;
                $product_image = isset($row['image'])     ? $row['image']     : '';
                $product_id    = isset($row['item_id'])   ? $row['item_id']   : 0;
                ?>

                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;
                            box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">

                    <!-- Item Image -->
                    <?php if (!empty($product_image) && file_exists("image/" . $product_image)): ?>
                        <img src="image/<?php echo htmlspecialchars($product_image); ?>" 
                             alt="Product Image" 
                             style="width: 100%; height: 180px; object-fit: cover;">
                    <?php else: ?>
                        <div style="height:180px; background:#f1f2f6; display:flex; align-items:center; justify-content:center; color:#a4b0be; font-size:13px; font-weight:600;">
                            No Image Available
                        </div>
                    <?php endif; ?>

                    <!-- Item Details -->
                    <div style="padding: 15px; display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between; text-align: left;">
                        <div>
                            <h3 style="color: #1e293b; font-size: 16px; margin: 0 0 8px 0; font-weight: 600; line-height: 1.4;">
                                <?php echo htmlspecialchars($product_name); ?>
                            </h3>
                            <div style="color: #10b981; font-weight: 700; font-size: 16px; margin-bottom: 15px;">
                                RM <?php echo number_format($product_price, 2); ?>
                            </div>
                        </div>


                        <a href="index.php?page=2&item_id=<?php echo (int)$product_id; ?>" 
                           style="display: block; text-align: center; background-color: #f1f5f9; color: #1e293b;
                                  padding: 8px 0; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 13px;">
                            View Detail
                        </a>
                    </div>
                </div>

                <?php
            }
        } else {
            echo "<p style='text-align:center; width:100%; color: #64748b; grid-column: 1 / -1; padding: 40px 0;'>No items found matching your search.</p>";
        }
        ?>
    </div>

</div>
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['userid'])) {
    js_alert_redirect('Please login first!', 'index.php');
}

$error_msg = "";

$item_id = 0;
if (isset($_GET['item_id'])) {
    $item_id = (int)$_GET['item_id'];
} elseif (isset($_POST['item_id'])) {
    $item_id = (int)$_POST['item_id'];
}

if ($item_id <= 0) {
    echo "<div style='text-align:center; padding:50px; color:#ef4444; font-weight:600;'>⚠️ Error: Item ID not found!</div>";
    return;
}

$current_user_id = (int)$_SESSION['userid'];
$user_role       = $_SESSION['role'] ?? 1;

// Only the owner (or an admin) may edit
if ($user_role == 3) {
    $query_check = "SELECT * FROM item WHERE item_id = $item_id";
} else {
    $query_check = "SELECT * FROM item WHERE item_id = $item_id AND user_id = $current_user_id";
}
$result_check = mysqli_query($dataconn, $query_check);

if ($result_check && mysqli_num_rows($result_check) > 0) {
    $item_data = mysqli_fetch_assoc($result_check);
    $old_image = $item_data['image'];
} else {
    js_alert_redirect('Access Denied: You do not have permission to edit this item!', 'index.php?page=1');
}

// ── Handle save ──
if (isset($_POST['update_item'])) {
    $item_name    = trim($_POST['item_name'] ?? '');
    $price_raw    = trim($_POST['price'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $condition_id = (int)($_POST['condition_id'] ?? 0);

    if (!csrf_valid()) {
        $error_msg = "Your session expired. Please try again.";
    } elseif ($item_name === '' || strlen($item_name) > 150) {
        $error_msg = "Item name is required (max 150 characters).";
    } elseif (!is_numeric($price_raw) || (float)$price_raw <= 0 || (float)$price_raw > 99999999) {
        $error_msg = "Please enter a valid price greater than 0.";
    } elseif ($description === '') {
        $error_msg = "Description is required.";
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $error_msg = "Please enter a valid phone number.";
    } elseif ($category_id <= 0 || $condition_id <= 0) {
        $error_msg = "Please choose a category and condition.";
    } else {
        $file_name = $old_image;
        $replaced_image = false;

        if (isset($_FILES['myfile']) && $_FILES['myfile']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_error = '';
            $new_name = save_uploaded_image($_FILES['myfile'], $upload_error);
            if ($new_name === null) {
                $error_msg = $upload_error;
            } else {
                $file_name = $new_name;
                $replaced_image = true;
            }
        }

        if ($error_msg === "") {
            $price  = number_format((float)$price_raw, 2, '.', '');
            $e_name = mysqli_real_escape_string($dataconn, $item_name);
            $e_desc = mysqli_real_escape_string($dataconn, $description);
            $e_ph   = mysqli_real_escape_string($dataconn, $phone);
            $e_file = mysqli_real_escape_string($dataconn, (string)$file_name);

            $query_update = "UPDATE item SET
                                item_name = '$e_name',
                                price = '$price',
                                description = '$e_desc',
                                phone_number = '$e_ph',
                                image = '$e_file',
                                category_id = $category_id,
                                condition_id = $condition_id
                             WHERE item_id = $item_id";
            if ($user_role != 3) {
                $query_update .= " AND user_id = $current_user_id";
            }

            if (mysqli_query($dataconn, $query_update)) {
                if ($replaced_image) {
                    delete_image_if_unused($dataconn, $old_image);
                }
                js_alert_redirect('Item updated successfully!', 'index.php?page=2&item_id=' . $item_id);
            } else {
                error_log('edit_item.php update failed: ' . mysqli_error($dataconn));
                if ($replaced_image) { @unlink('image/' . $file_name); }
                $error_msg = "Could not update the item. Please try again.";
            }
        }
    }

    // Re-show what the user typed if validation failed
    $item_data['item_name']    = $item_name;
    $item_data['price']        = $price_raw;
    $item_data['description']  = $description;
    $item_data['phone_number'] = $phone;
    $item_data['category_id']  = $category_id;
    $item_data['condition_id'] = $condition_id;
}

$cats  = mysqli_query($dataconn, "SELECT category_id, category_name FROM categories ORDER BY category_id");
$conds = mysqli_query($dataconn, "SELECT condition_id, condition_name FROM `condition` ORDER BY condition_id");

$field_style = "width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; background-color: #f8fafc;";
$label_style = "display: block; margin-bottom: 8px; color: #334155; font-weight: 600; font-size: 14px;";
?>

<div style="max-width: 600px; margin: 30px auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <div style="background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">

        <h2 style="margin-top: 0; color: #1e293b; font-size: 22px; font-weight: 700; text-align: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">
            ✏️ Edit Product Details
        </h2>

        <?php if (!empty($error_msg)): ?>
            <div style="background-color: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; font-weight: 500;">
                <?php echo h($error_msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=5&item_id=<?php echo $item_id; ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">

            <div style="margin-bottom: 20px;">
                <label style="<?php echo $label_style; ?>">Product Name:</label>
                <input type="text" name="item_name" maxlength="150" value="<?php echo h($item_data['item_name']); ?>" required style="<?php echo $field_style; ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="<?php echo $label_style; ?>">Price (RM):</label>
                <input type="number" step="0.01" min="0.01" name="price" value="<?php echo h($item_data['price']); ?>" required style="<?php echo $field_style; ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="<?php echo $label_style; ?>">Category:</label>
                <select name="category_id" required style="<?php echo $field_style; ?>">
                    <option value="">-- Select Category --</option>
                    <?php if ($cats): while ($c = mysqli_fetch_assoc($cats)): ?>
                        <option value="<?php echo (int)$c['category_id']; ?>" <?php if ((int)$item_data['category_id'] === (int)$c['category_id']) echo 'selected'; ?>>
                            <?php echo h($c['category_name']); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="<?php echo $label_style; ?>">Condition:</label>
                <select name="condition_id" required style="<?php echo $field_style; ?>">
                    <option value="">-- Select Condition --</option>
                    <?php if ($conds): while ($c = mysqli_fetch_assoc($conds)): ?>
                        <option value="<?php echo (int)$c['condition_id']; ?>" <?php if ((int)$item_data['condition_id'] === (int)$c['condition_id']) echo 'selected'; ?>>
                            <?php echo h(ucfirst($c['condition_name'])); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="<?php echo $label_style; ?>">Description / Details:</label>
                <textarea name="description" rows="5" required style="<?php echo $field_style; ?> resize: vertical;"><?php echo h($item_data['description']); ?></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="<?php echo $label_style; ?>">Contact Number:</label>
                <input type="text" name="phone" maxlength="20" value="<?php echo h($item_data['phone_number']); ?>" required style="<?php echo $field_style; ?>">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="<?php echo $label_style; ?>">Product Image:</label>

                <?php if (!empty($old_image) && file_exists("image/" . $old_image)): ?>
                    <div style="margin-bottom: 12px;">
                        <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 6px;">Current Image:</span>
                        <img src="image/<?php echo h(rawurlencode($old_image)); ?>" alt="Current image" style="width: 120px; height: auto; border-radius: 6px; border: 1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    </div>
                <?php endif; ?>

                <input type="file" name="myfile" accept="image/jpeg,image/png,image/gif,image/webp" style="font-size: 14px; color: #475569;">
                <small style="color: #94a3b8; font-size: 12px; display: block; margin-top: 6px;">*Leave empty if you don't want to change the image. JPG, PNG, GIF or WEBP, max 5 MB.</small>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" name="update_item" style="flex: 2; background-color: #3b82f6; color: #ffffff; padding: 12px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    💾 Save Changes
                </button>
                <a href="index.php?page=2&item_id=<?php echo $item_id; ?>" style="flex: 1; text-align: center; background-color: #ef4444; color: #ffffff; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; box-sizing: border-box;">
                    ❌ Cancel
                </a>
            </div>

        </form>
    </div>
</div>

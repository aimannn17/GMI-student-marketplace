<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include_once 'connectdbmarketplace.php';
require_once 'helpers.php';

if (!isset($_SESSION['userid'])) {
    js_alert_redirect('Please login first!', 'login.php');
}
if (($_SESSION['role'] ?? 1) == 3) {
    js_alert_redirect('Admin accounts cannot sell items.', 'index.php?page=8');
}
?>

<style>
    
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

    .form-container {
        font-family: 'Inter', sans-serif;
        max-width: 600px;
        margin: 40px auto;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    .form-container h2 {
        color: #1a202c;
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .form-subtitle {
        color: #718096;
        font-size: 14px;
        margin-bottom: 24px;
    }

    .form-group {
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-size: 14px;
        font-weight: 500;
        color: #4a5568;
        margin-bottom: 6px;
    }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        font-size: 14px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background-color: #fff;
        color: #334155;
        box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

   
    .form-group input[type="text"]:focus,
    .form-group input[type="number"]:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .form-group textarea {
        resize: vertical;
    }

   
    .file-input {
        padding: 6px 0;
        font-size: 14px;
    }

    
    .btn-submit-form {
        background-color: #3b82f6; /* Warna biru standard UI */
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        width: 100%;
        transition: background-color 0.2s;
        margin-top: 10px;
    }

    .btn-submit-form:hover {
        background-color: #2563eb;
    }
</style>

<div class="form-container">
    <h2>Sell Item</h2>
    <p class="form-subtitle">Fill in the details of your item to be displayed on the marketplace</p>

    <form action="action_up.php" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="item_name" maxlength="150" required>
        </div>

        <div class="form-group">
            <label>Price (RM):</label>
            <input type="number" name="price" min="0.01" max="99999999" step="0.01" required>
        </div>

        <div class="form-group">
            <label>Category:</label>
            <select name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php
             
                if (isset($dataconn) && is_object($dataconn)) {
                    $query_cat = mysqli_query($dataconn, "SELECT * FROM categories");
                    if ($query_cat) {
                        while ($row_cat = mysqli_fetch_assoc($query_cat)) {
                            echo "<option value='" . (int)$row_cat['category_id'] . "'>" . h($row_cat['category_name']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>Error: could not load categories</option>";
                    }
                } else {
                    echo "<option value=''>Error: database not connected</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Condition:</label>
            <select name="condition_id" required>
                <option value="">-- Select Condition --</option>
                <?php
                if (isset($dataconn) && is_object($dataconn)) {
                    $query_cond = mysqli_query($dataconn, "SELECT * FROM `condition`");
                    if ($query_cond) {
                        while ($row_cond = mysqli_fetch_assoc($query_cond)) {
                            echo "<option value='" . (int)$row_cond['condition_id'] . "'>" . h(ucfirst($row_cond['condition_name'])) . "</option>";
                        }
                    } else {
                        echo "<option value=''>Error: could not load conditions</option>";
                    }
                } else {
                    echo "<option value=''>Error: database not connected</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" name="phone" maxlength="20" placeholder="e.g., 0123456789" required>
        </div>

        <div class="form-group">
            <label>Upload Image:</label>
            <input type="file" name="myfile" class="file-input" accept="image/jpeg,image/png,image/gif,image/webp" required>
            <small style="color:#94a3b8; font-size:12px; margin-top:4px;">JPG, PNG, GIF or WEBP, max 5 MB.</small>
        </div>

        <input type="submit" value="Add Item" class="btn-submit-form" name="submit_item">
    </form>
</div>
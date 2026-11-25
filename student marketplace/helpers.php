<?php
/**
 * Shared helpers: output escaping, CSRF protection, safe JS alerts,
 * and secure image upload. Include with require_once 'helpers.php';
 */
if (session_status() == PHP_SESSION_NONE) { session_start(); }

/** Escape text for HTML output. */
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* ───────────── CSRF ───────────── */

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Hidden input to place inside every state-changing <form>. */
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

/** Query-string fragment for state-changing links, e.g. href="...&<?php echo csrf_query(); ?>" */
function csrf_query() {
    return 'csrf=' . urlencode(csrf_token());
}

/** True when the request carries the correct CSRF token (POST or GET). */
function csrf_valid() {
    $sent = $_POST['csrf'] ?? ($_GET['csrf'] ?? '');
    return !empty($_SESSION['csrf']) && is_string($sent) && hash_equals($_SESSION['csrf'], $sent);
}

/* ───────────── JS alert helpers (safe against quotes/HTML in messages) ───────────── */

function js_alert_redirect($message, $url) {
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    echo '<script>alert(' . json_encode($message, $flags) . '); window.location.href=' . json_encode($url, $flags) . ';</script>';
    exit();
}

function js_alert_back($message) {
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    echo '<script>alert(' . json_encode($message, $flags) . '); window.history.back();</script>';
    exit();
}

/* ───────────── Image upload ───────────── */

/**
 * Validate and store an uploaded image.
 * - max 5 MB
 * - must be a real jpg/png/gif/webp image (checked by content, not just extension)
 * - saved under a random name so files can never overwrite each other
 *
 * @param array  $file   one entry of $_FILES
 * @param string $error  filled with a human-readable message on failure
 * @param string $dir    target folder (with trailing slash)
 * @return string|null   stored file name, or null on failure
 */
function save_uploaded_image($file, &$error, $dir = 'image/') {
    $error = '';

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please choose an image to upload.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)
               ? 'Image is too large (max 5 MB).'
               : 'Image upload failed. Please try again.';
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $error = 'Image is too large (max 5 MB).';
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    $map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    if ($info === false || !isset($map[$info['mime']])) {
        $error = 'Only JPG, PNG, GIF or WEBP images are allowed.';
        return null;
    }

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        $error = 'Server could not create the image folder.';
        return null;
    }

    $name = bin2hex(random_bytes(8)) . '.' . $map[$info['mime']];

    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        $error = 'Server could not save the image. Check that the "image" folder is writable.';
        return null;
    }
    return $name;
}

/**
 * Delete an image file only when no other item still uses it
 * (older items may share an original file name).
 */
function delete_image_if_unused($dataconn, $image_name, $dir = 'image/') {
    if ($image_name === '' || $image_name === null) return;
    $safe  = mysqli_real_escape_string($dataconn, $image_name);
    $res   = mysqli_query($dataconn, "SELECT COUNT(*) AS c FROM item WHERE image = '$safe'");
    $count = $res ? (int)mysqli_fetch_assoc($res)['c'] : 1;
    $path  = $dir . basename($image_name);
    if ($count === 0 && is_file($path)) {
        @unlink($path);
    }
}
?>

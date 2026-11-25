<?php
/**
 * Optional helper for guest-mode checks.
 * (index.php already computes $is_logged_in / $is_guest itself; include this file
 *  from other pages only if you want the helper functions below.)
 */
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$is_logged_in = isset($_SESSION['userid']);
$is_guest     = !$is_logged_in && isset($_SESSION['guest']) && $_SESSION['guest'] === true;

/**
 * Stop the page and send guests (or visitors) to the login prompt.
 * Logged-in users pass straight through.
 */
if (!function_exists('guest_require_login')) {
    function guest_require_login($message = "You must login to perform this action.") {
        global $is_logged_in, $is_guest;

        if ($is_logged_in) return;

        if ($is_guest) {
            $encoded = urlencode($message);
            echo "<script>window.location.href='index.php?page=1&show_modal=1&modal_msg={$encoded}';</script>";
        } else {
            echo "<script>window.location.href='login.php';</script>";
        }
        exit();
    }
}

/**
 * Render a button: guests get the "login required" modal, members get a normal link.
 */
if (!function_exists('guest_action_button')) {
    function guest_action_button($label, $href, $message = "Login required.", $style = "") {
        global $is_guest;

        $default_style = "display: inline-block; padding: 10px 20px; background-color: #1e40af;
                          color: white; border-radius: 8px; text-decoration: none;
                          font-weight: 600; font-size: 14px; border: none; cursor: pointer;
                          font-family: inherit; " . $style;

        $label_html = htmlspecialchars($label, ENT_QUOTES);

        if ($is_guest) {
            $escaped_msg = htmlspecialchars(addslashes($message), ENT_QUOTES);
            return "<button onclick=\"showGuestModal('{$escaped_msg}')\" style=\"{$default_style}\">{$label_html}</button>";
        }
        return "<a href=\"" . htmlspecialchars($href, ENT_QUOTES) . "\" style=\"{$default_style}\">{$label_html}</a>";
    }
}

if (!function_exists('is_guest')) {
    function is_guest() { global $is_guest; return $is_guest; }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() { global $is_logged_in; return $is_logged_in; }
}
?>

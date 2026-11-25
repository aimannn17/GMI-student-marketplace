<?php
// Database connection for GMI Student Marketplace.
// Change these values if your MySQL/MariaDB setup is different.
mysqli_report(MYSQLI_REPORT_OFF); // PHP 8.1+ throws exceptions by default; keep classic return-value behaviour

$dataconn = @mysqli_connect("localhost", "root", "", "marketplace");

if (!$dataconn) {
    die("Database connection failed: " . mysqli_connect_error() .
        "<br>Did you import <code>marketplace.sql</code> into MySQL?");
}

mysqli_set_charset($dataconn, "utf8mb4");
?>

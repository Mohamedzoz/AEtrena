<?php
require_once 'config.php';

if (isLoggedIn()) {
    logActivity('تسجيل الخروج', 'قام ' . $_SESSION['user_name'] . ' بتسجيل الخروج من النظام.');
}

session_destroy();
header("Location: index.php");
exit;
?>

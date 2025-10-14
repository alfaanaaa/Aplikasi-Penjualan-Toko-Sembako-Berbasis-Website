ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Current directory: " . __DIR__ . "<br>";  // Debug path
<?php
include_once 'config/auth.php';
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>
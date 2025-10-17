<?php
session_start();

// Fungsi check login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['level']);
}

// Fungsi check level
function checkLevel($required_level = 'user') {
    if (!isLoggedIn()) return false;
    if ($required_level == 'admin' && $_SESSION['level'] != 'admin') return false;
    return true;
}

// Fungsi proteksi (redirect jika gagal)
function requireLogin($required_level = 'user', $redirect = 'login.php') {
    if (!checkLevel($required_level)) {
        $_SESSION['error'] = "Akses ditolak! Login sebagai " . ($required_level == 'admin' ? 'Admin' : 'User') . " diperlukan.";
        header("Location: $redirect");
        exit;
    }
}

// Auto-logout setelah 30 menit (opsional)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['last_activity'] = time();
?>

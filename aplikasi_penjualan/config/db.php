<?php
$host = 'localhost';
$port = 3306;
$dbname = 'toko_alfa';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage() . "<br>Cek XAMPP MySQL dan database 'toko_alfa'.");
}
?>

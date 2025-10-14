<?php
include 'config/db.php';

$id = $_GET['id'] ?? 0;

// Ambil data transaksi utama
$stmt = $pdo->prepare("
    SELECT 
        t.id_transaksi,
        p.nama_pembeli,
        t.total_harga,
        t.tanggal
    FROM transaksi t
    JOIN pembeli p ON t.id_pembeli = p.id_pembeli
    WHERE t.id_transaksi = ?
");
$stmt->execute([$id]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    echo "<p>Data transaksi tidak ditemukan!</p>";
    exit;
}

// Ambil semua detail barangnya
$stmt2 = $pdo->prepare("
    SELECT b.nama_barang, td.jumlah, b.harga
    FROM transaksi_detail td
    JOIN barang b ON td.id_barang = b.id_barang
    WHERE td.id_transaksi = ?
");
$stmt2->execute([$id]);
$detail = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Struk Belanja</title>
<style>
body {
    font-family: 'Courier New', monospace;
    padding: 15px;
    font-size: 13px;
    color: #3a2c1e;
}
.center { text-align: center; }
.line { border-top: 1px dashed #8B4513; margin: 6px 0; }
table { width: 100%; border-collapse: collapse; }
td { padding: 2px 0; vertical-align: top; }
.total { font-weight: bold; color: #5c3b09; }
.right { text-align: right; }
</style>
</head>
<body>

<div class="center">
    <img src="assetts/img/logo.png" alt="Logo" style="width:50px;height:auto;margin-bottom:8px;">
    <div><strong>TOKO SEMBAKO ALFA</strong></div>
    <div>Jl. KH. Ahmad Dahlan No.11,Kauman,Lamongan.</div>
</div>

<div class="line"></div>
<table>
    <tr><td>Tanggal</td><td>: <?= date('d/m/Y', strtotime($transaksi['tanggal'])) ?></td></tr>
    <tr><td>Pembeli</td><td>: <?= htmlspecialchars($transaksi['nama_pembeli']) ?></td></tr>
</table>
<div class="line"></div>

<!-- Barang per item -->
<table>
    <?php foreach ($detail as $item): ?>
    <tr>
        <td><?= htmlspecialchars($item['nama_barang']) ?></td>
        <td class="right"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<div class="line"></div>

<table>
    <tr>
        <td class="total">TOTAL</td>
        <td class="right total">Rp <?= number_format($transaksi['total_harga'], 0, ',', '.') ?></td>
    </tr>
</table>

<div class="line"></div>
<div class="center">
    Terima kasih sudah berbelanja😊<br>
    Barang yang dibeli tidak bisa dikembalikan<br>
    <?php date_default_timezone_set('Asia/Jakarta'); ?>
    Dicetak: <?= date('d/m/Y H:i') ?>
</div>

<script>
window.print();
</script>

</body>
</html>
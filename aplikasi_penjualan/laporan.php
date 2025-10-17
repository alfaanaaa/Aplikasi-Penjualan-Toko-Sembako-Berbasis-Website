<?php
include 'config/db.php';
include 'config/auth.php';
include 'includes/header.php';

requireLogin('user');

$message = ''; 
$error = '';

// Filter tanggal (default: 30 hari terakhir sampai hari ini)
$tanggal_dari = $_GET['tanggal_dari'] ?? date('Y-m-d', strtotime('-30 days'));
$tanggal_sampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

// Validasi tanggal
if (strtotime($tanggal_dari) > strtotime($tanggal_sampai)) {
    $error = "Tanggal dari tidak boleh lebih besar dari tanggal sampai!";
}

$transaksi_list = [];
$total_trans = 0;
$total_pendapatan = 0;

try {
    if (empty($error)) {
        //Ambil data transaksi sesuai format di halaman transaksi
        $stmt = $pdo->prepare("
            SELECT 
                t.id_transaksi, 
                p.nama_pembeli, 
                GROUP_CONCAT(CONCAT(b.nama_barang, ' (', td.jumlah, ')') SEPARATOR ', ') AS nama_barang,
                GROUP_CONCAT(FORMAT(b.harga, 0) SEPARATOR ', ') AS harga_satuan,
                SUM(td.jumlah) AS total_jumlah,
                t.total_harga,
                t.tanggal
            FROM transaksi t
            LEFT JOIN pembeli p ON t.id_pembeli = p.id_pembeli
            LEFT JOIN transaksi_detail td ON t.id_transaksi = td.id_transaksi
            LEFT JOIN barang b ON td.id_barang = b.id_barang
            WHERE t.tanggal BETWEEN ? AND ?
            GROUP BY t.id_transaksi, p.nama_pembeli, t.tanggal
            ORDER BY t.id_transaksi ASC
        ");
        $stmt->execute([$tanggal_dari, $tanggal_sampai]);
        $transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hitung total transaksi dan total pendapatan
        $stmt_total = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT t.id_transaksi) AS total_trans,
                COALESCE(SUM(td.jumlah * b.harga), 0) AS total_pendapatan
            FROM transaksi t
            JOIN transaksi_detail td ON t.id_transaksi = td.id_transaksi
            JOIN barang b ON td.id_barang = b.id_barang
            WHERE t.tanggal BETWEEN ? AND ?
        ");
        $stmt_total->execute([$tanggal_dari, $tanggal_sampai]);
        $total_data = $stmt_total->fetch(PDO::FETCH_ASSOC);

        $total_trans = $total_data['total_trans'];
        $total_pendapatan = $total_data['total_pendapatan'];
    }
} catch (PDOException $e) {
    $error = "Error query laporan: " . $e->getMessage();
}
?>

<h1>Laporan Transaksi Toko Sembako Alfa</h1>
<p class="lead">Laporan penjualan sembako berdasarkan rentang tanggal. Gunakan tombol cetak untuk PDF/printer.</p>

<form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
        <label for="tanggal_dari" class="form-label">Tanggal Dari</label>
        <input 
            type="date" 
            class="form-control" 
            name="tanggal_dari" 
            id="tanggal_dari"
            value="<?= htmlspecialchars($tanggal_dari) ?>" 
            required
        >
    </div>

    <div class="col-md-4">
        <label for="tanggal_sampai" class="form-label">Tanggal Sampai</label>
        <input 
            type="date" 
            class="form-control" 
            name="tanggal_sampai" 
            id="tanggal_sampai"
            value="<?= htmlspecialchars($tanggal_sampai) ?>" 
            required
        >
    </div>

    <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary me-2">Filter Laporan</button>
        <a href="laporan.php" class="btn btn-secondary">Reset (30 Hari Terakhir)</a>
    </div>
</form>


<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php elseif (empty($transaksi_list)): ?>
    <div class="alert alert-info">Tidak ada transaksi sembako pada rentang tanggal <?= htmlspecialchars($tanggal_dari) ?> s/d <?= htmlspecialchars($tanggal_sampai) ?>.</div>
<?php else: ?>

<div class="mb-3">
    <button type="button" onclick="cetakLaporan()" class="btn btn-success">
        🖨️ Cetak Laporan
    </button>
    <span class="ms-3 text-muted">
        Rentang: <?= htmlspecialchars($tanggal_dari) ?> s/d <?= htmlspecialchars($tanggal_sampai) ?> |
        Total Transaksi: <?= number_format($total_trans, 0, ',', '.') ?> |
        Pendapatan: Rp <?= number_format($total_pendapatan, 0, ',', '.') ?>
    </span>
</div>

<div class="table-responsive" id="laporan-content">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID Transaksi</th>
                <th>Pembeli</th>
                <th>Barang</th>
                <th>Harga Satuan (Rp)</th>
                <th>Jumlah</th>
                <th>Total Harga (Rp)</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transaksi_list as $transaksi): ?>
            <tr>
                <td><?= $transaksi['id_transaksi'] ?></td>
                <td><?= htmlspecialchars($transaksi['nama_pembeli']) ?></td>
                <td><?= htmlspecialchars($transaksi['nama_barang']) ?></td>
                <td><?= htmlspecialchars($transaksi['harga_satuan']) ?></td>
                <td><?= $transaksi['total_jumlah'] ?></td>
                <td>Rp <?= number_format($transaksi['total_harga'], 0, ',', '.') ?></td>
                <td><?= date('d/m/Y', strtotime($transaksi['tanggal'])) ?></td>
                <td>
                    <button class="btn btn-outline-success btn-sm" 
                        onclick="cetakStruk(
                            '<?= htmlspecialchars($transaksi['id_transaksi']) ?>'
                        )">
                        🧾 Struk
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-success">
            <tr class="total-cream">
                <th colspan="5" class="text-end" style="background-color: #FFF3CD;">Total Keseluruhan:</th>
                <th style="background-color: #FDF6E3;">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></th>
                <th colspan="2" style="background-color: #FDF6E3;">(<?= number_format($total_trans, 0, ',', '.') ?> Transaksi)</th>
            </tr>
        </tfoot>
    </table>
</div>
<?php endif; ?>


<style>
body {
    background: linear-gradient(135deg, #fffaf0, #f5e6ca);
    color: #4b2e05;
    font-family: 'Segoe UI', sans-serif;
}
h1 {
    color: #8B4513;
    font-weight: 700;
    border-bottom: 3px solid #D2B48C;
    padding-bottom: 8px;
    margin-bottom: 20px;
}
.table thead {
    background: linear-gradient(to right, #8B4513, #D2691E);
    color: #fff;
}
.table-striped tbody tr:nth-of-type(odd) { background-color: #f9f3e3; }
.table-striped tbody tr:hover { background-color: #f1e3c6; }
.total-cream {
    background-color: #FFF3CD;
    color: #4B3E02;
    font-weight: bold;
}
.btn-primary { background-color: #8B4513; border: none; }
.btn-primary:hover { background-color: #A0522D; }
.btn-secondary { background-color: #D2B48C; border: none; }
.btn-success { background-color: #B8860B; border: none; }
.alert-info { background-color: #f1e0c6; color: #4b2e05; }
</style>

<div class="print-footer" style="display:none;text-align:center;margin-top:20px;color:#4b2e05;font-weight:bold;">
    Laporan Toko Sembako Alfa | Periode: <?= htmlspecialchars($tanggal_dari) ?> s/d <?= htmlspecialchars($tanggal_sampai) ?> | Total: Rp <?= number_format($total_pendapatan,0,',','.') ?> | Dicetak: <?= date('d/m/Y H:i:s') ?>
</div>

<script>
function cetakLaporan() {
    // Ambil elemen tabel dalam .table-responsive
    var tabel = document.querySelector('.table-responsive table');
    if (!tabel) {
        alert("❗ Tabel laporan tidak ditemukan.");
        return;
    }

    // Kloning tabel
    var tabelClone = tabel.cloneNode(true);

    // index kolom "Aksi" 
    var indexAksi = -1;
    tabelClone.querySelectorAll('th').forEach((th, i) => {
        if (th.innerText.trim().toLowerCase() === 'aksi') {
            indexAksi = i;
        }
    });

    // Jika kolom Aksi ditemukan, hapus kolom itu di semua baris
    if (indexAksi !== -1) {
        tabelClone.querySelectorAll('tr').forEach(row => {
            if (row.cells.length > indexAksi) {
                row.deleteCell(indexAksi);
            }
        });
    }

    // Buat style
    var style = `
        <style>
            @page { size: A4 landscape; margin: 15mm; }
            body {
                font-family: Arial, sans-serif;
                color: #000;
                background: #fff;
                padding: 15px;
                font-size: 12px;
            }
            h2, h3, p {
                text-align: center;
                margin: 4px 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: #fff;
            }
            th, td {
                border: 1px solid #000;
                padding: 6px 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                text-align: center;
                font-weight: bold;
            .footer {
                float: right;
                text-align: right;
                margin-top: 40px;
                margin-left: 10px;
            }
            }
            .logo-toko {
                width: 30px; /* kecilkan sesuai kebutuhan */
                height: auto;
                margin-bottom: 10px;
            }
        </style>
    `;

    // Buka tab baru untuk mencetak
    var printWindow = window.open('', '_blank', 'width=900,height=700');
    printWindow.document.open();
    printWindow.document.write(`
        <html>
        <head><title>Laporan Transaksi</title>${style}</head>
        <body>
            <div class="kop-laporan">
                <img src="assetts/img/logo.png" alt="Logo Toko Alfa" style="display: block; margin: 0 auto; width: 100px; height: auto;">
                <h2>TOKO SEMBAKO ALFA</h2>
                <h3>Laporan Transaksi Penjualan</h3>
                <p>Jl. KH. Ahmad Dahlan No. 11, Kauman, Lamongan</p>
                <p>Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
            <div>

            ${tabelClone.outerHTML}
            
            <div class="footer-row" style="display: flex; justify-content: flex-end; margin-top: 40px;">
            <div class="footer-kanan" style="text-align: right;">
                <p>Mengetahui,</p>
                <p><strong>Pemilik Toko</strong></p>
                <br><br><br>
                <p><u>Alfa Qorina</u></p>
            </div>
            </div>

        </body>
        </html>
    `);
    printWindow.document.close();

    // Jalankan print setelah halaman siap
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
}

function cetakStruk(idTransaksi) {
    const win = window.open('ajax_struk.php?id=' + idTransaksi, '_blank', 'width=380,height=600');
}
</script>


<?php include 'includes/footer.php'; ?>

<?php
include 'config/db.php';
include 'config/auth.php';
include 'includes/header.php';

requireLogin('user');

// Agregat data
$stmt = $pdo->query("SELECT COUNT(*) as total FROM transaksi");
$total_trans = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(total_harga), 0) as pendapatan FROM transaksi");
$total_pendapatan = $stmt->fetch(PDO::FETCH_ASSOC)['pendapatan'];

$stmt = $pdo->query("
    SELECT b.nama_barang, COALESCE(SUM(td.jumlah), 0) as total_jual 
    FROM barang b 
    LEFT JOIN transaksi_detail td ON b.id_barang = td.id_barang 
    GROUP BY b.id_barang 
    ORDER BY total_jual DESC 
    LIMIT 1
");
$barang_terlaris = $stmt->fetch(PDO::FETCH_ASSOC);

$chart_stmt = $pdo->query("
    SELECT 
        DATE(tanggal) AS hari,
        COUNT(*) AS total_transaksi,
        SUM(total_harga) AS total_pendapatan
    FROM transaksi
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(tanggal)
    ORDER BY hari ASC
");

$hari = [];
$total_transaksi = [];
$total_pendapatan_chart = [];

while ($row = $chart_stmt->fetch(PDO::FETCH_ASSOC)) {
    $hari[] = $row['hari'];
    $total_transaksi[] = $row['total_transaksi'];
    $total_pendapatan_chart[] = $row['total_pendapatan'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(180deg, #fff8f0, #f9e7d3);
        color: #3b2f1e;
        margin: 0;
        padding: 0;
    }

    h3 {
        font-weight: 700;
        color: #c47f1a;
        letter-spacing: 0.5px;
    }

    .lead {
        color: #555;
        margin-bottom: 40px;
    }

    /* Container utama */
    .section {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    /* ====== MENU ====== */
    .menu-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 25px;
        margin: 50px 0;
    }

    .menu-card {
        width: 210px;
        height: 160px;
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-decoration: none;
        font-weight: 600;
        font-size: 18px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        color: #fff;
    }

    .menu-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 18px rgba(0,0,0,0.12);
        opacity: 0.95;
    }

    .menu-card h5 {
        margin-top: 12px;
        font-size: 16px;
    }

    /* Warna natural khas toko desa */
    .menu-card.brown { background: linear-gradient(135deg, #7a4e24, #9c6a2e); }
    .menu-card.caramel { background: linear-gradient(135deg, #e49b37, #c47f1a); }
    .menu-card.beige { background: linear-gradient(135deg, #f1d6a5, #d2b48c); color: #3b2f1e; }
    .menu-card.cream { background: linear-gradient(135deg, #f8e9cc, #f5d7a4); color: #4b2e05; }

    /* ====== RINGKASAN PENJUALAN ====== */
    .summary-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 30px;
        margin-top: 20px;
    }

    .summary-card {
        flex: 1;
        min-width: 260px;
        border-radius: 18px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .summary-card h5 {
        font-weight: 600;
        margin-bottom: 10px;
        letter-spacing: 0.5px;
    }

    .summary-card h2 {
        font-weight: 700;
        margin: 0;
    }

    .bg-primary { background: linear-gradient(135deg, #5a3e2b, #8b5a2b); color: #fff; }
    .bg-primary h2 { color: #fff5d1; text-shadow: 0 1px 2px rgba(0,0,0,0.2); }
    .bg-success { background: linear-gradient(135deg, #e49b37, #c47f1a); color: #fff; }
    .bg-info { background: linear-gradient(135deg, #f5deb3, #e6c79c); color: #3b2f1e; }

    /* ====== TENTANG WEBSITE ====== */
    .about-section {
        background: #fff;
        border-radius: 20px;
        padding: 40px;
        color: #3b2f1e;
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        margin-top: 80px;
        line-height: 1.7;
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }

        .chart-container {
        background: #fff;
        border-radius: 18px;
        padding: 20px;
        margin-top: 40px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        max-width: 650px;
        margin-left: auto;
        margin-right: auto;
    }
    .chart-container h3 {
        text-align: center;
        font-size: 18px;
        font-weight: 600;
        color: #5a3e2b;
        margin-bottom: 10px;
    }
    canvas {
        width: 100% !important;
        height: 260px !important;
    }

    .about-section h3 {
        margin-bottom: 20px;
    }

    /* ====== MAP ====== */
    .map-container {
        margin-top: 60px;
    }

    .map-container h3 {
        text-align: center;
    }

    .map-responsive {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        margin-top: 20px;
    }

    .map-responsive iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }

    /* ====== RESPONSIVE ====== */
    @media (max-width: 768px) {
        .menu-card {
            width: 150px;
            height: 130px;
            font-size: 16px;
        }
        .summary-row {
            flex-direction: column;
            align-items: center;
        }
    }
</style>

<!-- KONTEN -->
<div class="section text-center">
    <img src="assetts/img/toko.png" alt="Toko Sembako Alfa" style="display: block; margin: 0 auto; width: 480px; border-radius: 15px; box-shadow: 0 5px 12px rgba(0,0,0,0.1);">
    
    <h3 style="margin-top: 50px;">Manajemen Toko</h3>

    <div class="menu-container">
        <a href="barang.php" class="menu-card brown">🛒<h5>Kelola Barang</h5></a>
        <a href="pembeli.php" class="menu-card beige">🧑‍🤝‍🧑<h5>Kelola Pembeli</h5></a>
        <a href="transaksi.php" class="menu-card caramel">💰<h5>Kelola Transaksi</h5></a>
        <a href="laporan.php" class="menu-card cream">📊<h5>Laporan Transaksi</h5></a>
    </div>

    <p class="lead">Ringkasan penjualan sembako.</p>
    

    <div class="summary-row">
        <div class="summary-card bg-primary">
            <h5>📦 Total Transaksi</h5>
            <h2><?= number_format($total_trans, 0, ',', '.') ?></h2>
        </div>
        <div class="summary-card bg-success">
            <h5>💰 Total Pendapatan</h5>
            <h2>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h2>
        </div>
        <div class="summary-card bg-info">
            <h5>🔥 Barang Terlaris</h5>
            <h6><?= $barang_terlaris['nama_barang'] ?: 'Belum Ada Transaksi' ?></h6>
            <small>(Terjual: <?= number_format($barang_terlaris['total_jual'], 0, ',', '.') ?> unit)</small>
        </div>
    </div>
<br>
    <div class="chart-container">
        <h3>📅 Grafik Penjualan Harian</h3>
        <canvas id="chartPenjualanHarian"></canvas>
    </div>

    <script>
    const ctx = document.getElementById('chartPenjualanHarian').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($hari) ?>,
            datasets: [{
                label: 'Total Transaksi',
                data: <?= json_encode($total_transaksi) ?>,
                backgroundColor: 'rgba(196,127,26,0.7)',
                borderColor: '#c47f1a',
                borderWidth: 1
            }, {
                label: 'Pendapatan (Rp)',
                data: <?= json_encode($total_pendapatan_chart) ?>,
                backgroundColor: 'rgba(90,62,43,0.6)',
                borderColor: '#5a3e2b',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#3b2f1e', font: { size: 13 } } },
                title: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 200000, // kelipatan Rp200.000
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    },
                    suggestedMax: 1000000, // batas maksimum Rp1.000.000
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    title: { display: true, text: 'Pendapatan', color: '#5a3e2b', font: { weight: '600' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#3b2f1e' }
                }
            }
        }
    });
    </script>


    <div class="about-section">
        <h3>🛍️ Tentang Website Toko Sembako Alfa</h3>
        <p>
            Website ini dirancang untuk membantu pengelolaan data penjualan pada Toko Sembako Alfa agar lebih digital dan efisien. 
            Pengguna dapat mencatat transaksi, mengelola data barang, serta memantau pendapatan harian secara real-time.
        </p>
        <p>
            Toko Sembako Alfa berkomitmen menyediakan kebutuhan pokok masyarakat dengan harga terjangkau dan pelayanan cepat. 
            Dengan sistem penjualan berbasis web ini, diharapkan seluruh proses bisnis menjadi lebih mudah, transparan, dan akurat.
        </p>
        <p>
            Terima kasih telah menggunakan Aplikasi Penjualan Toko Sembako Alfa. 
            Semoga aplikasi ini membantu meningkatkan efisiensi dan akurasi pengelolaan usaha Anda.
        </p>
    </div>

    <div class="map-container">
        <h3>📍 Lokasi Toko Sembako Alfa</h3>
        <div class="map-responsive">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.0308233320616!2d112.4167832!3d-7.122425699999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e77f0b2ac1bc5cb%3A0x86ba9da9283e358a!2sJl.%20KH.%20Ahmad%20Dahlan%20No.11%2C%20Kauman%2C%20Sidoharjo%2C%20Kec.%20Lamongan%2C%20Kabupaten%20Lamongan%2C%20Jawa%20Timur%2062217!5e0!3m2!1sid!2sid!4v1760149339705!5m2!1sid!2sid"
                allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

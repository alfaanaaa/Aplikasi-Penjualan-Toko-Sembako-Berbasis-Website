<?php 
// Path fix: Include auth dari root
include_once __DIR__ . '/../config/auth.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Penjualan Toko Sembako Alfa</title>
    <body align="center">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <style>
        /* 🌟 Warna tema utama */
        :root {
            --warna-utama: #8B4513;     /* coklat tua */
            --warna-sekunder: #D2691E;  /* oranye kecoklatan */
            --warna-cerah: #F5DEB3;     /* krem muda */
            --warna-tulisan: #fff;
        }

        /* 🌈 Latar belakang halaman */
        body {
            background: linear-gradient(135deg, #fffaf0, #f5e6ca);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        /* 🌐 Navbar */

                .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: var(--warna-cerah) !important;
            font-size: 1.25rem;
        }

        .navbar-brand img {
            height: 45px;
            width: 45px;
            object-fit: contain;
            margin-right: 10px;
            border-radius: 8px;
            background: #fff8f0;
            padding: 5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .navbar-brand img:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }

         {
            display: flex;
            justify-content: center; /* posisi horizontal */
            align-items: center;     /* posisi vertikal */
            height: 100vh;           /* seluruh tinggi layar */
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }
        h1 {
            color: #333;
        }

        .brand-text {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            letter-spacing: 0.5px;
            color: var(--warna-cerah);
        }

        .navbar {
            background: linear-gradient(to right, var(--warna-utama), var(--warna-sekunder)) !important;
        }

        .navbar-brand, .nav-link, .navbar-text {
            color: var(--warna-cerah) !important;
            font-weight: 500;
        }

        .nav-link:hover, .navbar-brand:hover {
            color: #fff !important;
        }

        /* 🔘 Tombol */
        .btn-primary {
            background: linear-gradient(to right, var(--warna-utama), var(--warna-sekunder));
            border: none;
            color: var(--warna-tulisan);
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--warna-sekunder), var(--warna-utama));
            transform: scale(1.03);
        }

        .btn-secondary {
            background-color: var(--warna-sekunder);
            border: none;
            color: var(--warna-tulisan);
        }

        .btn-secondary:hover {
            background-color: var(--warna-utama);
        }

        /* 🧾 Card dan konten */
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .card-header {
            background: linear-gradient(to right, var(--warna-utama), var(--warna-sekunder));
            color: #fff;
            font-weight: 600;
            text-align: center;
        }

        /* 📊 Tabel */
        table thead {
            background-color: var(--warna-utama);
            color: #fff;
        }

        table tbody tr:hover {
            background-color: #f8f1e9;
        }

        /* 🔔 Alert */
        .alert-success {
            background-color: #f0f8e2;
            border-left: 5px solid var(--warna-sekunder);
        }

        .alert-danger {
            background-color: #fdecea;
            border-left: 5px solid #c0392b;
        }

        /* 🏷️ Judul halaman */
        h1, h2, h3 {
            color: var(--warna-utama);
            font-weight: 700;
        }

                /* === Sidebar Modern === */
        .offcanvas {
            width: 260px;
            border: none;
            background: #fff;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }

        .offcanvas-header {
            border-bottom: 1px solid #f0e6dc;
            padding: 1rem 1.25rem;
        }

        .offcanvas-title {
            font-weight: 700;
            color: var(--warna-utama);
            font-size: 1.25rem;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin: 4px 10px;
            color: var(--warna-text);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .menu-link i {
            font-size: 1.25rem;
            margin-right: 12px;
            color: var(--warna-utama);
            transition: 0.2s;
        }

        .menu-link:hover {
            background-color: var(--warna-hover);
            transform: translateX(4px);
        }

        .menu-link:hover i {
            color: var(--warna-text);
        }
        
        <script>
            document.addEventListener("DOMContentLoaded", () => {
            const burger = document.querySelector(".burger-menu");
            const sidebar = document.querySelector("#sidebarMenu");

            burger.addEventListener("click", () => {
                burger.classList.toggle("active");
            });

            sidebar.addEventListener("hidden.bs.offcanvas", () => {
                burger.classList.remove("active");
            });
            });
            </script>
            
        /* Footer */
        footer {
            text-align: center;
            color: #7a6b58;
            margin-top: 40px;
        }

        .btn-outline-primary {
            border-color: var(--warna-utama);
            color: var(--warna-utama);
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: var(--warna-sekunder);
            color: #fff;
            border-color: var(--warna-sekunder);
        }
    </style>
</head>

<body>

            <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
            <!-- Tombol Sidebar -->
<!-- Tombol Burger Modern -->
<button class="btn btn-outline-light border-0 shadow-sm me-3" 
        type="button" 
        data-bs-toggle="offcanvas" 
        data-bs-target="#sidebarMenu" 
        aria-controls="sidebarMenu"
        style="background: var(--warna-utama); border-radius: 8px;">
    <!-- Ikon Burger Modern (SVG) -->
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="white" viewBox="0 0 24 24">
        <path d="M3 6h18M3 12h18M3 18h18" stroke="white" stroke-width="2" stroke-linecap="round"/>
    </svg>
</button>
            
            <a class="navbar-brand" href="./dashboard.php">
                <img src="assetts/img/logo.png">
                Toko Sembako Alfa
            </a>

            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?= $_SESSION['nama_lengkap'] ?> (<?= $_SESSION['level'] ?>)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="./logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="./login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <!-- === SIDEBAR (OFFCANVAS) === -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title"><i class="bi bi-shop me-2"></i>Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
                <div class="offcanvas-body p-0">
            <a href="dashboard.php" class="menu-link"><i class="bi bi-house-door"></i>Dashboard</a>
            <a href="barang.php" class="menu-link"><i class="bi bi-box"></i>Data Barang</a>
            <a href="pembeli.php" class="menu-link"><i class="bi bi-people"></i>Data Pembeli</a>
            <a href="transaksi.php" class="menu-link"><i class="bi bi-cart4"></i>Transaksi</a>
            <a href="laporan.php" class="menu-link"><i class="bi bi-clipboard-data"></i>Laporan</a>
            <a href="logout.php" class="menu-link text-danger"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </div>
    </div>


    <div class="container mt-4">
        <?php 
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'dashboard.php'): ?>
    <div class="mb-3">
        <a href="./dashboard.php" class="btn btn-outline-primary d-inline-flex align-items-center">
            <i class="bi bi-arrow-left-circle me-2"></i> Kembali ke Dashboard
        </a>
    </div>
<?php endif; ?>
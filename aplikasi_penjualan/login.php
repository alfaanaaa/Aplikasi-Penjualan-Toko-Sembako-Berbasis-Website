<?php 
include 'config/db.php';
include 'config/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib!";
    } else {
        // Ambil data berdasarkan username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Cek password dan user
        if ($user && $user['password'] == $password) {
            // Simpan semua data penting ke session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap']; // Tambahan
            $_SESSION['level'] = $user['level'];
            $_SESSION['last_activity'] = time();

            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Sembako Alfa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('assetts/img/banner.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            padding: 25px;
        }

        .logo-box {
            background: #f6f1eb;
            border-radius: 10px;
            padding: 10px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .logo-box img {
            width: 90px;
            height: auto;
        }

        .card-header {
            background: transparent;
            border-bottom: none;
        }

        /* Tombol login dengan nuansa coklat-cream */
        .btn-primary {
            background-color: #8B4513 !important;
            border-color: #8B4513 !important;
            color: #fff8f0 !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #A0522D !important;
            border-color: #A0522D !important;
            color: #fff !important;
            transform: scale(1.03);
        }

        .btn-primary:active {
            background-color: #5C4033 !important;
            border-color: #5C4033 !important;
            color: #fff8f0 !important;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="card-header text-center">
            <div class="logo-box">
                <img src="assetts/img/logo.png" alt="Logo Toko Sembako Alfa">
            </div>
            <h3 class="mt-2">Login Toko Sembako Alfa</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <div class="mt-3 text-center">
                <small>Alamat : Jl. KH. Ahmad Dahlan No.11 , Kauman, Lamongan</small>
                <p><small>WhatsApp Admin : 0881036296001 (Alfa)</small></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
include 'config/db.php';
include 'config/auth.php';
include 'includes/header.php';

requireLogin('user');
$message = ''; $error = '';

// Inisialisasi pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// prepared statement untuk pencarian
if ($search !== '') {
    $stmt_list = $pdo->prepare("SELECT * FROM barang WHERE nama_barang LIKE ? ORDER BY id_barang ASC");
    $stmt_list->execute(['%' . $search . '%']);
} else {
    $stmt_list = $pdo->prepare("SELECT * FROM barang ORDER BY id_barang ASC");
    $stmt_list->execute();
}
$barang_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);


// CREATE/UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && checkLevel('admin')) {
    $action = $_POST['action'] ?? 'create';
    $id = $_POST['id_barang'] ?? null;
    $nama = strtoupper(trim($_POST['nama_barang']));
    $harga = floatval($_POST['harga']);
    $stok = intval($_POST['stok']);

    if (empty($nama) || $harga <= 0 || $stok < 0) {
        $error = "Error: Nama barang sembako wajib, harga >0, stok >=0!";
    } else {
        if ($action == 'create') {
            $stmt = $pdo->prepare("INSERT INTO barang (nama_barang, harga, stok) VALUES (?, ?, ?)");
            $stmt->execute([$nama, $harga, $stok]);
            $message = "Barang sembako berhasil ditambahkan!";
        } else {
            $stmt = $pdo->prepare("UPDATE barang SET nama_barang=?, harga=?, stok=? WHERE id_barang=?");
            $stmt->execute([$nama, $harga, $stok, $id]);
            $message = "Barang sembako berhasil diupdate!";
        }
    }
}

// DELETE
if (isset($_POST['delete_id']) && checkLevel('admin')) {
    $id = intval($_POST['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM barang WHERE id_barang=?");
    $stmt->execute([$id]);
    $message = "Barang sembako berhasil dihapus!";
}

// READ: List dan edit data
$edit_id = intval($_GET['edit'] ?? 0);
$edit_data = [];
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE id_barang=?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<style>
    body {
        background-color: #fff8f0; /* krem lembut */
    }

    h1, h2, h3, p, label, th, td {
        color: #5C4033;
    }

    .btn-primary {
        background-color: #D2691E !important; /* oranye hangat */
        border-color: #D2691E !important;
    }
    .btn-primary:hover {
        background-color: #A0522D !important;
        border-color: #A0522D !important;
    }

    .btn-warning {
        background-color: #F4A460 !important;
        border-color: #F4A460 !important;
        color: #5C4033 !important;
    }

    .btn-danger {
        background-color: #8B0000 !important;
        border-color: #8B0000 !important;
    }

    .btn-secondary {
        background-color: #C0A080 !important;
        border-color: #C0A080 !important;
        color: #fff !important;
    }

    /* 🟤 Header tabel warna coklat tua */
    .table-dark {
        background-color: #d2b48c !important; /* tan / coklat muda */
        color: #5c4033 !important; /* teks coklat tua */
        border-color: #c19a6b !important;
    }

    /* Hover dan border tabel */
    .table-hover tbody tr:hover {
        background-color: #f7e6c4 !important; /* cream lebih terang */
    }

    /* Baris ganjil biar tetap lembut */
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #fffaf0 !important; /* krem lembut */
    }

    .badge.bg-success {
        background-color: #D2691E !important;
    }

    .badge.bg-danger {
        background-color: #A52A2A !important;
    }

    .alert-success {
        background-color: #F5DEB3;
        color: #5C4033;
        border-color: #DEB887;
    }

    .alert-warning {
        background-color: #FFF8DC;
        color: #8B4513;
        border-color: #FFD700;
    }

    .card, .table, form {
        border-radius: 10px;
    }

    input.form-control {
        border: 1px solid #DEB887;
        background-color: #fffaf2;
        color: #5C4033;
    }

    input.form-control:focus {
        border-color: #D2691E;
        box-shadow: 0 0 4px rgba(210,105,30,0.5);
    }

    .text-muted {
        color: #8B7355 !important;
    }
</style>


<h1>Kelola Barang Sembako</h1>
<p>Kelola stok barang sembako di toko anda dengan mudah disini.</p>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>


<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" class="form-control" name="search" placeholder="Cari nama barang..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
        <?php if ($search !== ''): ?>
            <a href="barang.php" class="btn btn-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        <?php endif; ?>
    </div>
</form>


<?php if (checkLevel('admin')): ?>
<form method="POST" class="row g-3 mb-4">
    <input type="hidden" name="action" value="<?= $edit_id ? 'update' : 'create' ?>">
    <?php if ($edit_id): ?>
        <input type="hidden" name="id_barang" value="<?= $edit_id ?>">
    <?php endif; ?>
    <div class="col-md-3">
        <input type="text" class="form-control" name="nama_barang" placeholder="Nama Barang Sembako (akan kapital)" 
               value="<?= $edit_data['nama_barang'] ?? '' ?>" required>
    </div>
    <div class="col-md-3">
        <input type="number" class="form-control" name="harga" placeholder="Harga (Rp)" step="0.01" min="0.01" 
               value="<?= $edit_data['harga'] ?? '' ?>" required>
    </div>
    <div class="col-md-3">
        <input type="number" class="form-control" name="stok" placeholder="Stok" min="0" 
               value="<?= $edit_data['stok'] ?? '' ?>" required>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-primary"><?= $edit_id ? 'Update' : 'Tambah' ?></button>
        <?php if ($edit_id): ?>
            <a href="barang.php" class="btn btn-secondary">Batal</a>
        <?php endif; ?>
    </div>
</form>
<?php else: ?>
    <div class="alert alert-warning">Anda bukan admin. Hanya bisa melihat list barang sembako.</div>
<?php endif; ?>


<div class="table-responsive">
<table class="table table-striped">
    <thead style="background-color: #5C4033; color: #f5e6ca;">
        <tr>
            <th>ID</th>
            <th>Nama Barang Sembako</th>
            <th>Harga (Rp)</th>
            <th>Stok</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($barang_list)): ?>
            <tr><td colspan="5" class="text-center">Belum ada barang sembako. Tambahkan sebagai admin!</td></tr>
        <?php else: ?>
            <?php foreach ($barang_list as $barang): ?>
            <tr>
                <td><?= $barang['id_barang'] ?></td>
                <td><?= htmlspecialchars($barang['nama_barang']) ?></td>
                <td>Rp <?= number_format($barang['harga'], 0, ',', '.') ?></td>
                <td>
                    <span class="badge <?= $barang['stok'] <= 5 ? 'bg-danger' : 'bg-success' ?>">
                        <?= $barang['stok'] ?> unit
                    </span>
                </td>
                <td>
                    <?php if (checkLevel('admin')): ?>
                        <a href="?edit=<?= $barang['id_barang'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Yakin hapus barang sembako ini?');">
                            <input type="hidden" name="delete_id" value="<?= $barang['id_barang'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">Lihat saja</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php include 'includes/footer.php'; ?>

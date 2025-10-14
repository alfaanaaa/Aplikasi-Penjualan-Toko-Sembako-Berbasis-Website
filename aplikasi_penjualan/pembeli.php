<?php
include 'config/db.php';
include 'config/auth.php';
include 'includes/header.php';

requireLogin('user');

$message = ''; 
$error = '';

// CREATE/UPDATE (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && checkLevel('admin')) {
    $action = $_POST['action'] ?? 'create';
    $id = $_POST['id_pembeli'] ?? null;
    $nama = trim($_POST['nama_pembeli']);
    $alamat = trim($_POST['alamat']);

    if (empty($nama) || empty($alamat)) {
        $error = "Error: Nama dan alamat pembeli sembako wajib!";
    } else {
        if ($action == 'create') {
            $stmt = $pdo->prepare("INSERT INTO pembeli (nama_pembeli, alamat) VALUES (?, ?)");
            $stmt->execute([$nama, $alamat]);
            $message = "Pembeli sembako berhasil ditambahkan!";
        } else {
            $stmt = $pdo->prepare("UPDATE pembeli SET nama_pembeli=?, alamat=? WHERE id_pembeli=?");
            $stmt->execute([$nama, $alamat, $id]);
            $message = "Pembeli sembako berhasil diupdate!";
        }
    }
}

// DELETE (admin only)
if (isset($_POST['delete_id']) && checkLevel('admin')) {
    $id = intval($_POST['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM pembeli WHERE id_pembeli=?");
    $stmt->execute([$id]);
    $message = "Pembeli sembako berhasil dihapus!";
}

// READ: List dengan pencarian
// Inisialisasi pencarian (letakkan setelah include/header dan requireLogin)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ambil daftar pembeli (prepared statement untuk pencarian)
if ($search !== '') {
    $stmt_list = $pdo->prepare("SELECT * FROM pembeli WHERE nama_pembeli LIKE ? ORDER BY id_pembeli ASC");
    $stmt_list->execute(['%' . $search . '%']);
} else {
    $stmt_list = $pdo->prepare("SELECT * FROM pembeli ORDER BY id_pembeli ASC");
    $stmt_list->execute();
}
$pembeli_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

// Edit data
$edit_id = intval($_GET['edit'] ?? 0);
$edit_data = [];
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM pembeli WHERE id_pembeli=?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
    body {
        background-color: #f9f4ee; /* Cream lembut */
        color: #4e342e; /* Coklat tua */
    }
    h1 {
        color: #6d4c41;
        border-bottom: 3px solid #d7ccc8;
        padding-bottom: 8px;
        margin-bottom: 20px;
    }
    .btn-primary {
        background-color: #a1887f;
        border-color: #8d6e63;
    }
    .btn-primary:hover {
        background-color: #8d6e63;
    }
    .btn-secondary {
        background-color: #d7ccc8;
        color: #4e342e;
        border: none;
    }
    .btn-secondary:hover {
        background-color: #bcaaa4;
        color: #3e2723;
    }
    .btn-warning {
        background-color: #ffb74d;
        border: none;
    }
    .btn-warning:hover {
        background-color: #ffa726;
    }
    .btn-danger {
        background-color: #e57373;
        border: none;
    }
    .btn-danger:hover {
        background-color: #ef5350;
    }
    .table thead {
        background-color: #8d6e63;
        color: #fff;
    }
    .alert-success {
        background-color: #d7ccc8;
        color: #3e2723;
        border: none;
    }
    .alert-danger {
        background-color: #ef9a9a;
        color: #4e342e;
        border: none;
    }
    .alert-warning {
        background-color: #ffe0b2;
        color: #5d4037;
        border: none;
    }
</style>

<h1>Kelola Pembeli Sembako</h1>
<p>Kelola data pembeli toko sembako anda dengan mudah disini.</p>

<!-- Form Pencarian (semua level) -->
<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" class="form-control" name="search" placeholder="Cari nama pembeli sembako..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary" type="submit">Cari</button>
        <?php if ($search): ?>
            <a href="pembeli.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<!-- Form Tambah/Edit (admin only) -->
<?php if (checkLevel('admin')): ?>
<form method="POST" class="row g-3 mb-4">
    <input type="hidden" name="action" value="<?= $edit_id ? 'update' : 'create' ?>">
    <?php if ($edit_id): ?>
        <input type="hidden" name="id_pembeli" value="<?= $edit_id ?>">
    <?php endif; ?>
    <div class="col-md-4">
        <input type="text" class="form-control" name="nama_pembeli" placeholder="Nama Pembeli Sembako" 
               value="<?= $edit_data['nama_pembeli'] ?? '' ?>" required>
    </div>
    <div class="col-md-6">
        <input type="text" class="form-control" name="alamat" placeholder="Alamat Lengkap" 
               value="<?= $edit_data['alamat'] ?? '' ?>" required>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary"><?= $edit_id ? 'Update' : 'Tambah' ?></button>
        <?php if ($edit_id): ?>
            <a href="pembeli.php" class="btn btn-secondary">Batal</a>
        <?php endif; ?>
    </div>
</form>
<?php else: ?>
    <div class="alert alert-warning">Anda bukan admin. Hanya bisa melihat dan mencari pembeli sembako.</div>
<?php endif; ?>

<!-- Tabel List -->
<div class="table-responsive">
<table class="table table-striped">
    <thead>
        <tr><th>ID</th><th>Nama Pembeli Sembako</th><th>Alamat</th><th>Aksi</th></tr>
    </thead>
    <tbody>
        <?php if (empty($pembeli_list)): ?>
            <tr><td colspan="4" class="text-center">Belum ada pembeli sembako<?php if ($search): ?> atau hasil pencarian kosong<?php endif; ?>. Tambahkan sebagai admin!</td></tr>
        <?php else: ?>
            <?php foreach ($pembeli_list as $pembeli): ?>
            <tr>
                <td><?= $pembeli['id_pembeli'] ?></td>
                <td><?= htmlspecialchars($pembeli['nama_pembeli']) ?></td>
                <td><?= htmlspecialchars($pembeli['alamat']) ?></td>
                <td>
                    <?php if (checkLevel('admin')): ?>
                        <a href="?edit=<?= $pembeli['id_pembeli'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Yakin hapus pembeli sembako ini?');">
                            <input type="hidden" name="delete_id" value="<?= $pembeli['id_pembeli'] ?>">
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

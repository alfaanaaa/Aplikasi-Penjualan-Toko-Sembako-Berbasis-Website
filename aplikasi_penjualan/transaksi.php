<?php
include 'config/db.php';
include 'config/auth.php';
include 'includes/header.php';

requireLogin('user');

$message = '';
$error = '';

$id_transaksi = 0;
$id_pembeli   = 0;
$id_barang    = 0;
$jumlah       = 0;
$tanggal      = date('Y-m-d');

//Ambil list pembeli dan barang untuk dropdown
$stmt = $pdo->query("
    SELECT 
        t.id_transaksi, 
        p.nama_pembeli, 
        GROUP_CONCAT(CONCAT(b.nama_barang, ' (', td.jumlah, ')') SEPARATOR ', ') AS barang_dibeli,
        t.total_harga, 
        t.tanggal
    FROM transaksi t
    LEFT JOIN pembeli p ON t.id_pembeli = p.id_pembeli
    LEFT JOIN transaksi_detail td ON t.id_transaksi = td.id_transaksi
    LEFT JOIN barang b ON td.id_barang = b.id_barang
    GROUP BY t.id_transaksi, p.nama_pembeli, t.tanggal
    ORDER BY t.id_transaksi ASC
");

$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pembeli_list = $pdo->query("SELECT id_pembeli, nama_pembeli FROM pembeli ORDER BY nama_pembeli")->fetchAll(PDO::FETCH_ASSOC);
$barang_list = $pdo->query("SELECT id_barang, nama_barang, harga, stok FROM barang ORDER BY nama_barang")->fetchAll(PDO::FETCH_ASSOC);

// CREATE Multi Barang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['action'] ?? '') == 'create') {
    $id_pembeli = intval($_POST['id_pembeli']);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];

    if ($id_pembeli <= 0 || empty($items)) {
        $error = "Error: Pilih pembeli dan minimal 1 barang sembako!";
    } else {
        try {
            $pdo->beginTransaction();

            // Hitung grand total dan validasi item
            $grand_total = 0;
            $valid_items = [];
            foreach ($items as $idx => $it) {
                $id_barang = intval($it['id_barang'] ?? 0);
                $jumlah = intval($it['jumlah'] ?? 0);

                if ($id_barang <= 0 || $jumlah <= 0) {
                    throw new Exception("Item #$idx: id_barang atau jumlah tidak valid.");
                }

                $stmt_barang = $pdo->prepare("SELECT harga, stok FROM barang WHERE id_barang = ?");
                $stmt_barang->execute([$id_barang]);
                $barang = $stmt_barang->fetch(PDO::FETCH_ASSOC);

                if (!$barang) {
                    throw new Exception("Item #$idx: Barang tidak ditemukan.");
                }
                if ($jumlah > (int)$barang['stok']) {
                    throw new Exception("Item #$idx: Stok barang tidak cukup.");
                }

                $subtotal = $barang['harga'] * $jumlah;
                $grand_total += $subtotal;
                $valid_items[] = [
                    'id_barang' => $id_barang,
                    'jumlah' => $jumlah,
                    'harga' => $barang['harga'],
                    'subtotal' => $subtotal
                ];
            }

            // Simpan ke tabel transaksi
            $stmt_trans = $pdo->prepare("INSERT INTO transaksi (id_pembeli, tanggal, total_harga) VALUES (?, ?, ?)");
            $stmt_trans->execute([$id_pembeli, $tanggal, $grand_total]);
            $id_transaksi_baru = $pdo->lastInsertId();

            // Simpan detail transaksi dan update stok
            $stmt_detail = $pdo->prepare("INSERT INTO transaksi_detail (id_transaksi, id_barang, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_update_stok = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id_barang = ?");

            foreach ($valid_items as $vi) {
                $stmt_detail->execute([$id_transaksi_baru, $vi['id_barang'], $vi['jumlah'], $vi['harga'], $vi['subtotal']]);
                $stmt_update_stok->execute([$vi['jumlah'], $vi['id_barang']]);
            }

            $pdo->commit();
            $message = "✅ Transaksi berhasil disimpan (ID: $id_transaksi_baru) Total: Rp " . number_format($grand_total, 0, ',', '.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "❌ Gagal menyimpan transaksi: " . $e->getMessage();
        }
    }
}



// DELETE
if (isset($_POST['delete_id']) && checkLevel('admin')) {
    $id_transaksi = intval($_POST['delete_id']);
    $stmt_data = $pdo->prepare("SELECT jumlah, id_barang FROM transaksi WHERE id_transaksi=?");
    $stmt_data->execute([$id_transaksi]);
    $data = $stmt_data->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        $pdo->prepare("UPDATE barang SET stok = stok + ? WHERE id_barang=?")->execute([$data['jumlah'], $data['id_barang']]);
    }
    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi=?");
    if ($stmt->execute([$id_transaksi])) {
        $message = "Transaksi berhasil dihapus! Stok dikembalikan.";
    } else {
        $error = "Gagal menghapus transaksi!";
    }
}

//READ List Transaksi
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$transaksi_list = [];

try {
    $query = "
        SELECT 
            t.id_transaksi,
            p.nama_pembeli,
            GROUP_CONCAT(CONCAT(b.nama_barang, ' (', td.jumlah, ')') SEPARATOR ', ') AS barang_dibeli,
            SUM(td.jumlah) AS total_jumlah,
            SUM(td.jumlah * td.harga) AS total_harga,
            t.tanggal
        FROM transaksi t
        LEFT JOIN pembeli p ON t.id_pembeli = p.id_pembeli
        LEFT JOIN transaksi_detail td ON t.id_transaksi = td.id_transaksi
        LEFT JOIN barang b ON td.id_barang = b.id_barang
    ";

    $params = [];
    $conditions = [];

    if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
        $conditions[] = "t.tanggal BETWEEN ? AND ?";
        $params[] = $tanggal_awal;
        $params[] = $tanggal_akhir;
    } elseif (!empty($tanggal_awal)) {
        $conditions[] = "t.tanggal >= ?";
        $params[] = $tanggal_awal;
    } elseif (!empty($tanggal_akhir)) {
        $conditions[] = "t.tanggal <= ?";
        $params[] = $tanggal_akhir;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " GROUP BY t.id_transaksi, p.nama_pembeli, t.tanggal ORDER BY t.id_transaksi ASC";

    $stmt_list = $pdo->prepare($query);
    $stmt_list->execute($params);
    $transaksi_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error query transaksi: " . $e->getMessage();
}
?>

<style>
body { background-color: #fff8f0; }
h1, h2, h3, p, label, th, td { color: #5C4033; }
.btn-primary { background-color: #D2691E !important; border-color: #D2691E !important; }
.btn-primary:hover { background-color: #A0522D !important; }
.btn-warning { background-color: #F4A460 !important; color: #5C4033 !important; }
.btn-danger { background-color: #8B0000 !important; border-color: #8B0000 !important; }
.table-hover tbody tr:hover { background-color: #f7e6c4 !important; }
.table-striped tbody tr:nth-of-type(odd) { background-color: #fffaf0 !important; }
.alert-success { background-color: #F5DEB3; color: #5C4033; border-color: #DEB887; }
.alert-danger { background-color: #FFE4E1; color: #8B0000; border-color: #FF6B6B; }
input.form-control, select.form-select {
    border: 1px solid #DEB887;
    background-color: #fffaf2;
    color: #5C4033;
}
input.form-control:focus, select.form-select:focus {
    border-color: #D2691E;
    box-shadow: 0 0 4px rgba(210,105,30,0.5);
}
.table th, .table td { vertical-align: middle; }
.total-cream th { background-color: #FFF3CD !important; color: #5C4033 !important; }
</style>

    <h1>Kelola Transaksi Sembako</h1>
    <p>Kelola semua data transaksi toko sembako anda dengan mudah disni.</p>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="get" class="mb-4">
    <div class="row g-3 align-items-end flex-wrap">
        <div class="col-auto">
            <label for="tanggal_awal" class="col-form-label">Dari tanggal:</label>
            <input type="date" 
                   name="tanggal_awal" 
                   id="tanggal_awal" 
                   class="form-control" 
                   value="<?= htmlspecialchars($_GET['tanggal_awal'] ?? '') ?>">
        </div>

        <div class="col-auto">
            <label for="tanggal_akhir" class="col-form-label">Sampai tanggal:</label>
            <input type="date" 
                   name="tanggal_akhir" 
                   id="tanggal_akhir" 
                   class="form-control" 
                   value="<?= htmlspecialchars($_GET['tanggal_akhir'] ?? '') ?>">
        </div>

        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-secondary">Terapkan</button>
            <a href="?" class="btn btn-warning">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow p-4 mb-5">
    <h4>Tambah Transaksi Baru</h4>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Pembeli</label>
                <select name="id_pembeli" class="form-select select2-pembeli" required>
                    <option value="">-- Pilih Pembeli --</option>
                    <?php foreach ($pembeli_list as $p): ?>
                        <option value="<?= $p['id_pembeli'] ?>"><?= htmlspecialchars($p['nama_pembeli']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div id="items-container">
            <div class="item-row row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Barang</label>
                    <select name="items[0][id_barang]" class="form-select select2-barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($barang_list as $b): ?>
                            <option value="<?= $b['id_barang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?> (Rp <?= number_format($b['harga'],0,',','.') ?> | Stok: <?= $b['stok'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="items[0][jumlah]" class="form-control" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary mt-3 add-item">+ Tambah</button>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Simpan Transaksi</button>
    </form>
</div>


    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>ID Transaksi</th>
                    <th>Nama Pembeli</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Total Harga</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
<?php if ($transaksi_list): ?>
    <?php foreach ($transaksi_list as $t): ?>
        <tr>
            <td><?= htmlspecialchars($t['id_transaksi']) ?></td>
            <td><?= htmlspecialchars($t['nama_pembeli']) ?></td>
            <td><?= htmlspecialchars($t['barang_dibeli']) ?></td>
            <td><?= htmlspecialchars($t['total_jumlah']) ?></td>
            <td>Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($t['tanggal']) ?></td>
            <td>
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $t['id_transaksi'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                        Hapus
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="7" class="text-center text-muted">Belum ada transaksi.</td>
    </tr>
<?php endif; ?>
</tbody>
        </table>
    </div>
</div>

<script>
let itemIndex = 1;

// Inisialisasi Select2 pertama kali
$(document).ready(function() {
    $('.select2-pembeli').select2({
        placeholder: '-- Pilih Pembeli --',
        width: '100%'
    });
    $('.select2-barang').select2({
        placeholder: '-- Pilih Barang --',
        width: '100%'
    });
});

// Tombol Tambah Barang
document.querySelector('.add-item').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.classList.add('item-row', 'row', 'g-3', 'align-items-end');
    newRow.innerHTML = `
        <div class="col-md-5">
            <label class="form-label">Barang</label>
            <select name="items[${itemIndex}][id_barang]" class="form-select select2-barang" required>
                <option value="">-- Pilih Barang --</option>
                <?php foreach ($barang_list as $b): ?>
                    <option value="<?= $b['id_barang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?> (Rp <?= number_format($b['harga'],0,',','.') ?> | Stok: <?= $b['stok'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Jumlah</label>
            <input type="number" name="items[${itemIndex}][jumlah]" class="form-control" min="1" value="1" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger btn-sm remove-item mt-3">Hapus</button>
        </div>`;

    container.appendChild(newRow);
    itemIndex++;

    // Inisialisasi Select2 untuk dropdown yang baru ditambahkan
    $(newRow).find('.select2-barang').select2({
        placeholder: '-- Pilih Barang --',
        width: '100%'
    });

    newRow.querySelector('.remove-item').addEventListener('click', function() {
        newRow.remove();
    });
});
</script>

<?php include 'includes/footer.php'; ?>

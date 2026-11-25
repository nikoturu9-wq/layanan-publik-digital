<?php
require_once "../config/koneksi.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$status_valid = ["Menunggu verifikasi", "Diproses", "Disetujui", "Ditolak"];
$filter = $_GET["status"] ?? "";

$sql = "SELECT p.id, p.created_at, p.status, u.nama, j.nama_surat
        FROM pengajuan p
        JOIN users u ON p.user_id = u.id
        JOIN jenis_surat j ON p.jenis_surat_id = j.id";

if (in_array($filter, $status_valid)) {
    $sql .= " WHERE p.status = ? ORDER BY p.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $filter);
} else {
    $sql .= " ORDER BY p.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
}
mysqli_stmt_execute($stmt);
$daftar = mysqli_stmt_get_result($stmt);

$warna = [
    "Menunggu verifikasi" => "secondary",
    "Diproses" => "warning",
    "Disetujui" => "success",
    "Ditolak" => "danger"
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <span class="navbar-brand">Admin - Layanan Publik Digital</span>
    <span class="text-white">
      <?= htmlspecialchars($_SESSION["nama"]) ?>
      <a href="../auth/logout.php" class="btn btn-sm btn-light ms-2">Keluar</a>
    </span>
  </div>
</nav>

<div class="container py-4">
  <?php if (isset($_GET["simpan"])): ?>
    <div class="alert alert-success">Verifikasi berhasil disimpan.</div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Daftar Pengajuan</h4>
    <form method="get" class="d-flex gap-2">
      <select name="status" class="form-select">
        <option value="">Semua status</option>
        <?php foreach ($status_valid as $s): ?>
          <option value="<?= $s ?>" <?= $filter === $s ? "selected" : "" ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline-dark">Filter</button>
    </form>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th>No</th>
            <th>Pemohon</th>
            <th>Jenis surat</th>
            <th>Tanggal</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 1; while ($row = mysqli_fetch_assoc($daftar)): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row["nama"]) ?></td>
            <td><?= htmlspecialchars($row["nama_surat"]) ?></td>
            <td><?= date("d-m-Y H:i", strtotime($row["created_at"])) ?></td>
            <td><span class="badge bg-<?= $warna[$row["status"]] ?>"><?= $row["status"] ?></span></td>
            <td><a href="verifikasi.php?id=<?= $row["id"] ?>" class="btn btn-sm btn-primary">Periksa</a></td>
          </tr>
        <?php endwhile; ?>
        <?php if ($no === 1): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada pengajuan.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
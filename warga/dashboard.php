<?php
require_once "../config/koneksi.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "warga") {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT p.id, p.created_at, p.status, p.catatan_admin, j.nama_surat
    FROM pengajuan p
    JOIN jenis_surat j ON p.jenis_surat_id = j.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
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
  <title>Dashboard Warga</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
  <div class="container">
    <span class="navbar-brand">Layanan Publik Digital</span>
    <span class="text-white">
      <?= htmlspecialchars($_SESSION["nama"]) ?>
      <a href="../auth/logout.php" class="btn btn-sm btn-light ms-2">Keluar</a>
    </span>
  </div>
</nav>

<div class="container py-4">
  <?php if (isset($_GET["ajukan"])): ?>
    <div class="alert alert-success">Pengajuan berhasil dikirim.</div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pengajuan Saya</h4>
    <a href="ajukan.php" class="btn btn-primary">+ Ajukan Surat</a>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>No</th>
            <th>Jenis surat</th>
            <th>Tanggal</th>
            <th>Status</th>
            <th>Catatan admin</th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 1; while ($row = mysqli_fetch_assoc($daftar)): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row["nama_surat"]) ?></td>
            <td><?= date("d-m-Y H:i", strtotime($row["created_at"])) ?></td>
            <td><span class="badge bg-<?= $warna[$row["status"]] ?>"><?= $row["status"] ?></span></td>
            <td><?= htmlspecialchars($row["catatan_admin"] ?? "-") ?></td>
          </tr>
        <?php endwhile; ?>
        <?php if ($no === 1): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pengajuan.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
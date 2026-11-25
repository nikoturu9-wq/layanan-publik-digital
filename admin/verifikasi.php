<?php
require_once "../config/koneksi.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$id = (int) ($_GET["id"] ?? 0);
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $status = $_POST["status"] ?? "";
    $catatan = trim($_POST["catatan_admin"] ?? "");
    $status_valid = ["Diproses", "Disetujui", "Ditolak"];

    if (!in_array($status, $status_valid)) {
        $error = "Pilih status yang valid.";
    } elseif ($status === "Ditolak" && $catatan === "") {
        $error = "Catatan wajib diisi jika pengajuan ditolak.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE pengajuan SET status = ?, catatan_admin = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $status, $catatan, $id);
        mysqli_stmt_execute($stmt);
        header("Location: dashboard.php?simpan=berhasil");
        exit;
    }
}

$stmt = mysqli_prepare($conn, "SELECT p.*, u.nama, u.email, j.nama_surat
    FROM pengajuan p
    JOIN users u ON p.user_id = u.id
    JOIN jenis_surat j ON p.jenis_surat_id = j.id
    WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    die("Pengajuan tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verifikasi Pengajuan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 640px;">
  <a href="dashboard.php">&larr; Kembali</a>
  <div class="card shadow-sm mt-3">
    <div class="card-body p-4">
      <h4 class="mb-3">Verifikasi Pengajuan #<?= $data["id"] ?></h4>

      <table class="table table-sm">
        <tr><th style="width:35%">Pemohon</th><td><?= htmlspecialchars($data["nama"]) ?> (<?= htmlspecialchars($data["email"]) ?>)</td></tr>
        <tr><th>Email kontak</th><td><?= htmlspecialchars($data["kontak_email"] ?? "-") ?></td></tr>
<tr><th>No. telepon</th><td><?= htmlspecialchars($data["kontak_telepon"] ?? "-") ?></td></tr>
        <tr><th>Jenis surat</th><td><?= htmlspecialchars($data["nama_surat"]) ?></td></tr>
        <tr><th>Tanggal</th><td><?= date("d-m-Y H:i", strtotime($data["created_at"])) ?></td></tr>
        <tr><th>Keterangan</th><td><?= nl2br(htmlspecialchars($data["keterangan"] ?? "-")) ?></td></tr>
        <tr><th>Berkas</th><td><a href="../uploads/<?= htmlspecialchars($data["berkas"]) ?>" target="_blank">Lihat berkas</a></td></tr>
        <tr><th>Status saat ini</th><td><?= $data["status"] ?></td></tr>
      </table>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Keputusan</label>
          <select name="status" class="form-select" required>
            <option value="">-- Pilih --</option>
            <option value="Diproses">Diproses</option>
            <option value="Disetujui">Setujui</option>
            <option value="Ditolak">Tolak</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Catatan (wajib jika ditolak)</label>
          <textarea name="catatan_admin" class="form-control" rows="3"><?= htmlspecialchars($data["catatan_admin"] ?? "") ?></textarea>
        </div>
        <button class="btn btn-primary w-100">Simpan Keputusan</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
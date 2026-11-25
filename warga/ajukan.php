<?php
require_once "../config/koneksi.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "warga") {
    header("Location: ../auth/login.php");
    exit;
}

$error = "";
$jenis = mysqli_query($conn, "SELECT id, nama_surat, persyaratan FROM jenis_surat ORDER BY nama_surat");

$jenis_id = 0;
$keterangan = "";
$kontak_email = "";
$kontak_telepon = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $jenis_id = (int) $_POST["jenis_surat_id"];
    $keterangan = trim($_POST["keterangan"]);
    $kontak_email = trim($_POST["kontak_email"]);
    $kontak_telepon = preg_replace("/[\s\-]/", "", trim($_POST["kontak_telepon"]));

    if ($jenis_id <= 0) {
        $error = "Pilih jenis surat.";
    } elseif ($kontak_email === "" && $kontak_telepon === "") {
        $error = "Isi minimal salah satu: alamat email atau nomor telepon.";
    } elseif ($kontak_email !== "" && !filter_var($kontak_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif ($kontak_telepon !== "" && !preg_match("/^(\+62|62|0)8[0-9]{7,12}$/", $kontak_telepon)) {
        $error = "Nomor telepon tidak valid. Contoh: 081234567890.";
    } elseif (!isset($_FILES["berkas"]) || $_FILES["berkas"]["error"] !== UPLOAD_ERR_OK) {
        $error = "Berkas wajib diunggah.";
    } else {
        $ekstensi = strtolower(pathinfo($_FILES["berkas"]["name"], PATHINFO_EXTENSION));
        $diizinkan = ["jpg", "jpeg", "png", "pdf"];

        if (!in_array($ekstensi, $diizinkan)) {
            $error = "Berkas harus berformat JPG, PNG, atau PDF.";
        } elseif ($_FILES["berkas"]["size"] > 2 * 1024 * 1024) {
            $error = "Ukuran berkas maksimal 2 MB.";
        } else {
            $nama_file = uniqid("berkas_", true) . "." . $ekstensi;
            move_uploaded_file($_FILES["berkas"]["tmp_name"], "../uploads/" . $nama_file);

            $email_simpan = $kontak_email === "" ? null : $kontak_email;
            $telepon_simpan = $kontak_telepon === "" ? null : $kontak_telepon;

            $stmt = mysqli_prepare($conn, "INSERT INTO pengajuan (user_id, jenis_surat_id, keterangan, kontak_email, kontak_telepon, berkas) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iissss", $_SESSION["user_id"], $jenis_id, $keterangan, $email_simpan, $telepon_simpan, $nama_file);
            mysqli_stmt_execute($stmt);
            header("Location: dashboard.php?ajukan=berhasil");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ajukan Surat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 560px;">
  <a href="dashboard.php">&larr; Kembali</a>
  <div class="card shadow-sm mt-3">
    <div class="card-body p-4">
      <h4 class="mb-3">Ajukan Surat</h4>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Jenis surat</label>
          <select name="jenis_surat_id" class="form-select" required>
            <option value="">-- Pilih --</option>
            <?php while ($j = mysqli_fetch_assoc($jenis)): ?>
              <option value="<?= $j["id"] ?>" <?= $jenis_id === (int) $j["id"] ? "selected" : "" ?>>
                <?= htmlspecialchars($j["nama_surat"]) ?> (<?= htmlspecialchars($j["persyaratan"]) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="3" placeholder="Tujuan pengajuan surat"><?= htmlspecialchars($keterangan) ?></textarea>
        </div>

        <div class="mb-1">
          <label class="form-label">Kontak yang bisa dihubungi</label>
          <div class="form-text mb-2">Isi minimal salah satu: email atau nomor telepon.</div>
        </div>
        <div class="mb-3">
          <input type="email" name="kontak_email" class="form-control" placeholder="Alamat email, contoh: nama@email.com" value="<?= htmlspecialchars($kontak_email) ?>">
        </div>
        <div class="mb-3">
          <input type="tel" name="kontak_telepon" class="form-control" placeholder="Nomor telepon, contoh: 081234567890" value="<?= htmlspecialchars($kontak_telepon) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Berkas pendukung (JPG, PNG, atau PDF, maks. 2 MB)</label>
          <input type="file" name="berkas" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Kirim Pengajuan</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
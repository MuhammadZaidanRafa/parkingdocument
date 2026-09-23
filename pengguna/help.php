<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panduan Pemesanan Parkir - E-Parkir</title>

<!-- Fonts & Framework Bootstrap 5 -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
  :root {
    --primary: #007bff;
    --primary-hover: #0056b3;
    --bg-light: #f4f6f9;
    --text-dark: #1e293b;
    --text-muted: #64748b;
  }

  body {
    background-color: var(--bg-light);
    font-family: 'Inter', sans-serif;
    color: var(--text-dark);
    padding-bottom: 60px;
  }

  .hero-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 40px 20px;
    border-radius: 0 0 20px 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
  }

  .btn-kembali {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    background: rgba(255,255,255,0.15);
    padding: 6px 14px;
    border-radius: 20px;
    transition: all 0.2s ease;
    backdrop-filter: blur(5px);
  }

  .btn-kembali:hover {
    color: white;
    background: rgba(255,255,255,0.25);
  }

  .card-guide {
    background: white;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }

  .step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0f2fe;
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
  }

  .accordion-item {
    border: none;
    margin-bottom: 10px;
    border-radius: 8px !important;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
  }

  .accordion-button:not(.collapsed) {
    background-color: #e0f2fe;
    color: var(--primary);
  }

  .contact-box {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border-left: 5px solid var(--primary);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }

  .note-box {
    background: #f0f9ff;
    border-left: 4px solid var(--primary);
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13px;
    color: var(--text-muted);
  }
</style>
</head>
<body>

<!-- Header Hero -->
<header class="hero-header text-center">
  <div class="container" style="max-width: 800px;">
    <div class="d-flex justify-content-start mb-3">
      <a href="../dashboard_pengguna.php" class="btn-kembali" onclick="if (window.history.length > 1) { window.history.back(); return false; }">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>
    <h1 class="fw-bold mb-2"><i class="fa-solid fa-circle-question me-2"></i> Panduan Pemesanan Parkir</h1>
    <p class="mb-0 opacity-90">Petunjuk langkah demi langkah untuk memesan slot parkir secara daring.</p>
  </div>
</header>

<div class="container" style="max-width: 900px;">

  <!-- Alur Pemesanan -->
  <div class="card card-guide p-4 mb-4">
    <h4 class="fw-bold mb-4 text-primary"><i class="fa-solid fa-list-check me-2"></i> Tata Cara Pemesanan Slot Parkir</h4>

    <div class="row g-4">
      <!-- Step 1 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">1</div>
          <div>
            <h6 class="fw-bold mb-1">Daftarkan Kendaraan</h6>
            <p class="text-muted small mb-0">Buka menu <strong>Kendaraan Saya</strong>, klik <strong>+ Daftarkan Kendaraan</strong>, lalu isi plat nomor dan jenis kendaraan (Motor atau Mobil). Warna dan nama pemilik bersifat opsional.</p>
          </div>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">2</div>
          <div>
            <h6 class="fw-bold mb-1">Cek Ketersediaan Slot</h6>
            <p class="text-muted small mb-0">Buka menu <strong>Pesan Tempat</strong>. Setiap area parkir menampilkan jumlah <strong>Terisi</strong>, <strong>Kapasitas</strong>, dan <strong>Tersedia</strong>. Area yang sudah penuh tidak dapat dipilih.</p>
          </div>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">3</div>
          <div>
            <h6 class="fw-bold mb-1">Lakukan Booking</h6>
            <p class="text-muted small mb-0">Klik <strong>+ Booking Tempat</strong> pada kendaraan Anda (atau lewat menu <strong>Pesan Tempat</strong>), lalu pilih area, tanggal, jam masuk, dan estimasi durasi (1–24 jam). Tanggal tidak boleh sebelum hari ini.</p>
          </div>
        </div>
      </div>

      <!-- Step 4 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">4</div>
          <div>
            <h6 class="fw-bold mb-1">Tunjukkan QR Masuk</h6>
            <p class="text-muted small mb-0">Saat tiba di lokasi, buka <strong>Lihat Kuitansi</strong> pada booking Anda, lalu tunjukkan <span class="badge bg-success">🟢 QR MASUK</span> kepada petugas untuk di-scan. Setelah itu status parkir Anda menjadi <strong>Aktif</strong>.</p>
          </div>
        </div>
      </div>

      <!-- Step 5 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">5</div>
          <div>
            <h6 class="fw-bold mb-1">Pantau Waktu Parkir</h6>
            <p class="text-muted small mb-0">Selama status <strong>Aktif</strong>, menu <strong>Riwayat Parkir</strong> menampilkan sisa waktu. Jika melewati estimasi durasi, akan tampil keterlambatan beserta perkiraan denda.</p>
          </div>
        </div>
      </div>

      <!-- Step 6 -->
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-3">
          <div class="step-number">6</div>
          <div>
            <h6 class="fw-bold mb-1">Keluar & Pembayaran</h6>
            <p class="text-muted small mb-0">Saat akan keluar, buka kuitansi lagi dan tunjukkan <span class="badge bg-danger">🔴 QR KELUAR</span> kepada petugas, lalu selesaikan pembayaran (tunai atau QRIS). Status booking berubah menjadi <strong>Selesai</strong>.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="note-box mt-4">
      <i class="fa-solid fa-circle-info me-1 text-primary"></i>
      Hanya satu QR yang tampil sesuai status booking: <strong>QR MASUK</strong> saat status menunggu, dan <strong>QR KELUAR</strong> saat status aktif.
    </div>
  </div>

  <!-- FAQ / Pertanyaan Sering Diajukan -->
  <div class="card card-guide p-4 mb-4">
    <h4 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-comments me-2"></i> Pertanyaan Umum (FAQ)</h4>

    <div class="accordion" id="faqAccordion">
      
      <!-- FAQ 1 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
            Bagaimana cara membatalkan pemesanan?
          </button>
        </h2>
        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Jika status booking masih <strong>Menunggu</strong> (belum di-scan petugas), Anda bisa menekan tombol <span class="badge bg-danger">❌ Batal Pesanan</span> pada menu <strong>Kendaraan Saya</strong>. Slot parkir akan dilepaskan kembali secara otomatis.
          </div>
        </div>
      </div>

      <!-- FAQ 2 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
            Bagaimana cara mencetak kuitansi booking?
          </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Buka menu <strong>Kendaraan Saya</strong>, lalu klik tautan <strong>Lihat Kuitansi</strong> di bawah rincian booking kendaraan Anda. Kuitansi juga tersedia lewat tombol <strong>Kuitansi</strong> pada menu <strong>Riwayat Parkir</strong>.
          </div>
        </div>
      </div>

      <!-- FAQ 3 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
            Berapa lama batas toleransi keterlambatan?
          </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Batas toleransi kedatangan adalah 15–30 menit dari jam masuk yang dipilih. Jika melebihi batas waktu tersebut tanpa konfirmasi, booking dapat dibatalkan oleh petugas.
          </div>
        </div>
      </div>

      <!-- FAQ 4 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
            Bagaimana jika parkir melebihi estimasi durasi?
          </button>
        </h2>
        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Pada menu <strong>Riwayat Parkir</strong>, kolom <strong>Sisa Waktu / Denda</strong> akan berubah menjadi keterlambatan beserta denda. Denda dihitung per jam keterlambatan (dibulatkan ke atas) sesuai tarif per jam kendaraan Anda.
          </div>
        </div>
      </div>

      <!-- FAQ 5 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
            Bagaimana cara membayar biaya parkir?
          </button>
        </h2>
        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Pembayaran dilakukan langsung kepada petugas saat Anda keluar, yaitu setelah <strong>QR KELUAR</strong> di-scan. Metode yang tersedia adalah tunai atau QRIS, dan metode yang dipakai akan tercantum pada kuitansi.
          </div>
        </div>
      </div>

      <!-- FAQ 6 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
            Semua area parkir penuh, apa yang harus dilakukan?
          </button>
        </h2>
        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Booking hanya bisa dibuat pada area yang masih memiliki slot tersedia. Jika semua area penuh, coba lagi beberapa saat kemudian atau pilih tanggal dan jam lain.
          </div>
        </div>
      </div>

      <!-- FAQ 7 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
            Bagaimana cara menghapus kendaraan?
          </button>
        </h2>
        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Di halaman <strong>Daftarkan Kendaraan</strong>, klik tombol <strong>Hapus</strong> pada kendaraan yang dimaksud. Kendaraan yang sedang parkir tidak dapat dihapus. Riwayat booking kendaraan yang dihapus tetap tersimpan.
          </div>
        </div>
      </div>

      <!-- FAQ 8 -->
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
            Bagaimana cara mengganti nama atau password akun?
          </button>
        </h2>
        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body text-muted small">
            Buka menu <strong>Profil</strong>, ubah nama lengkap, atau isi kolom <strong>Password Baru</strong> (minimal 6 karakter) jika ingin menggantinya, lalu simpan. Kosongkan kolom password bila tidak ingin mengubahnya.
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Kontak Bantuan -->
  <div class="contact-box d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <h5 class="fw-bold mb-1"><i class="fa-solid fa-headset text-primary me-2"></i> Butuh Bantuan Petugas?</h5>
      <p class="text-muted small mb-0">Jika terdapat kendala saat melakukan booking, silakan hubungi pusat layanan kami.</p>
    </div>
    <div>
      <a href="https://wa.me/6281326831712" target="_blank" rel="noopener" class="btn btn-success btn-sm px-3 fw-semibold">
        <i class="fa-brands fa-whatsapp me-1"></i> Hubungi WhatsApp
      </a>
    </div>
  </div>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
require_once "config/koneksi.php";

/* =========================
   CEK LOGIN USER
========================= */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)($_SESSION['user']['id'] ?? 0);

if ($user_id <= 0) {
    die("Session user tidak valid.");
}

/* =========================
   AMBIL ID LELANG
========================= */
$auction_id = (int)($_GET['id'] ?? 0);

if ($auction_id <= 0) {
    die("ID lelang tidak valid.");
}

/* =========================
   AMBIL DATA LELANG
========================= */
$stmt = $conn->prepare("
    SELECT 
        a.*,
        u.name AS winner_name
    FROM auctions a
    LEFT JOIN users u 
        ON u.id = a.winner_user_id
    WHERE a.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $auction_id);
$stmt->execute();

$auction = $stmt->get_result()->fetch_assoc();

if (!$auction) {
    die("Data lelang tidak ditemukan.");
}

/* =========================
   CEK APAKAH USER PEMENANG
========================= */
if ((int)$auction['winner_user_id'] !== $user_id) {
    die("Anda bukan pemenang lelang ini.");
}

/* =========================
   CEK STATUS LELANG
========================= */
if ($auction['status'] !== 'selesai') {
    die("Pembayaran belum tersedia karena lelang belum selesai.");
}

/* =========================
   AMBIL DATA PAYMENT
========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM payments
    WHERE auction_id = ?
      AND user_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$stmt->bind_param("ii", $auction_id, $user_id);
$stmt->execute();

$payment = $stmt->get_result()->fetch_assoc();

/* =========================
   DATA USER
========================= */
$stmt = $conn->prepare("
    SELECT name, username, email
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

$page_title = "Pembayaran - LelangKita";
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($page_title) ?></title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f3fa;
    color: #27213a;
}

/* =========================
   HEADER
========================= */

.header {
    height: 76px;
    background: #171126;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 55px;
    color: white;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 22px;
    font-weight: 700;
}

.logo-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #eee5ff;
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.user-name {
    font-size: 14px;
}

.user-name small {
    display: block;
    color: #bdb4d0;
    margin-top: 3px;
}

/* =========================
   CONTENT
========================= */

.container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 25px;
}

.breadcrumb {
    font-size: 13px;
    color: #8a819d;
    margin-bottom: 10px;
}

.breadcrumb a {
    color: #7c3aed;
    text-decoration: none;
    font-weight: 600;
}

.title {
    margin-bottom: 30px;
}

.title h1 {
    margin: 0 0 8px;
    font-size: 32px;
}

.title p {
    margin: 0;
    color: #8a819d;
}

/* =========================
   LAYOUT
========================= */

.payment-layout {
    display: grid;
    grid-template-columns: 1fr 1.15fr;
    gap: 25px;
}

/* =========================
   CARD
========================= */

.card {
    background: white;
    border: 1px solid #e9e4f1;
    border-radius: 20px;
    box-shadow: 0 10px 35px rgba(54, 37, 88, 0.07);
    overflow: hidden;
}

.card-header {
    padding: 23px 25px;
    border-bottom: 1px solid #eeeaf3;
}

.card-header h2 {
    margin: 0;
    font-size: 19px;
}

.card-header p {
    margin: 7px 0 0;
    font-size: 13px;
    color: #938ba1;
}

.card-body {
    padding: 25px;
}

/* =========================
   PRODUCT
========================= */

.product-image {
    width: 100%;
    height: 240px;
    object-fit: cover;
    border-radius: 15px;
    background: #f0ecf6;
}

.product-title {
    font-size: 22px;
    font-weight: 700;
    margin-top: 20px;
}

.price-label {
    margin-top: 20px;
    color: #91889f;
    font-size: 13px;
}

.price {
    font-size: 28px;
    font-weight: 800;
    color: #6d28d9;
    margin-top: 5px;
}

/* =========================
   DETAIL
========================= */

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 15px 0;
    border-bottom: 1px solid #eeeaf3;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #82798f;
}

.detail-value {
    font-weight: 700;
    text-align: right;
}

/* =========================
   PAYMENT METHOD
========================= */

.method-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 15px;
}

.method-option {
    display: block;
    position: relative;
    margin-bottom: 12px;
}

.method-option input {
    position: absolute;
    opacity: 0;
}

.method-box {
    border: 2px solid #e7e1ef;
    border-radius: 14px;
    padding: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: 0.2s;
}

.method-box:hover {
    border-color: #a855f7;
    background: #faf7ff;
}

.method-option input:checked + .method-box {
    border-color: #7c3aed;
    background: #f5efff;
}

.method-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: #eee7ff;
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.method-name {
    font-weight: 700;
}

.method-desc {
    font-size: 12px;
    color: #938ba1;
    margin-top: 4px;
}

/* =========================
   PAYMENT INFO
========================= */

.payment-info {
    margin-top: 20px;
    background: #faf8ff;
    border: 1px solid #ebe2fa;
    border-radius: 14px;
    padding: 17px;
    font-size: 13px;
    line-height: 1.7;
}

.payment-info strong {
    color: #6d28d9;
}

/* =========================
   BUTTON
========================= */

.btn-pay {
    width: 100%;
    border: none;
    margin-top: 22px;
    padding: 15px;
    border-radius: 12px;
    background: linear-gradient(135deg, #7c3aed, #9333ea);
    color: white;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(124, 58, 237, 0.25);
    transition: 0.2s;
}

.btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(124, 58, 237, 0.35);
}

.btn-back {
    display: inline-block;
    margin-top: 15px;
    color: #7c3aed;
    text-decoration: none;
    font-weight: 600;
}

/* =========================
   STATUS
========================= */

.status-box {
    padding: 18px;
    border-radius: 14px;
    margin-bottom: 20px;
}

.status-pending {
    background: #fff8e6;
    color: #9a6700;
    border: 1px solid #f5df9d;
}

.status-paid {
    background: #eafaf3;
    color: #13795b;
    border: 1px solid #b8ead7;
}

.status-failed {
    background: #fff0f0;
    color: #c62828;
    border: 1px solid #f1baba;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 800px) {

    .header {
        padding: 0 20px;
    }

    .user-name {
        display: none;
    }

    .payment-layout {
        grid-template-columns: 1fr;
    }

    .title h1 {
        font-size: 27px;
    }

}

</style>

</head>

<body>

<header class="header">

    <div class="logo">
        <div class="logo-icon">🔨</div>
        LelangKita
    </div>

    <div class="user-info">

        <div class="user-name">
            <?= htmlspecialchars($user['name'] ?? 'User') ?>
            <small>@<?= htmlspecialchars($user['username'] ?? '') ?></small>
        </div>

        <div class="user-avatar">
            <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
        </div>

    </div>

</header>


<main class="container">

    <div class="breadcrumb">
        <a href="index.php">Beranda</a>
        &nbsp;›&nbsp; Pembayaran
    </div>

    <div class="title">

        <h1>Pembayaran Lelang</h1>

        <p>
            Selesaikan pembayaran untuk barang lelang yang kamu menangkan.
        </p>

    </div>


    <div class="payment-layout">


        <!-- =====================
             KIRI
        ====================== -->

        <div class="card">

            <div class="card-header">

                <h2>📦 Detail Barang</h2>

                <p>
                    Barang yang berhasil kamu menangkan.
                </p>

            </div>

            <div class="card-body">

                <?php if (!empty($auction['image'])): ?>

                    <img
                        src="<?= htmlspecialchars($auction['image']) ?>"
                        class="product-image"
                        alt="<?= htmlspecialchars($auction['title']) ?>"
                    >

                <?php else: ?>

                    <div
                        class="product-image"
                        style="
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:60px;
                        "
                    >
                        📱
                    </div>

                <?php endif; ?>


                <div class="product-title">
                    <?= htmlspecialchars($auction['title']) ?>
                </div>


                <div class="price-label">
                    Harga kemenangan
                </div>

                <div class="price">
                    Rp <?= number_format((float)$auction['winner_bid'], 0, ',', '.') ?>
                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Pemenang
                    </span>

                    <span class="detail-value">
                        <?= htmlspecialchars($auction['winner_name']) ?>
                    </span>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Status Lelang
                    </span>

                    <span class="detail-value">
                        Selesai
                    </span>

                </div>


            </div>

        </div>


        <!-- =====================
             KANAN
        ====================== -->

        <div class="card">

            <div class="card-header">

                <h2>💳 Pembayaran</h2>

                <p>
                    Pilih metode pembayaran yang kamu inginkan.
                </p>

            </div>

            <div class="card-body">


                <?php if ($payment && $payment['payment_status'] === 'paid'): ?>

                    <div class="status-box status-paid">

                        <strong>✓ Pembayaran telah disetujui</strong>

                        <br>

                        Pembayaran untuk barang ini sudah dikonfirmasi oleh admin.

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Metode Pembayaran
                        </span>

                        <span class="detail-value">
                            <?= htmlspecialchars($payment['payment_method'] ?: 'Belum dipilih') ?>
                        </span>

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Nominal
                        </span>

                        <span class="detail-value">
                            Rp <?= number_format((float)$payment['amount'], 0, ',', '.') ?>
                        </span>

                    </div>


                <?php else: ?>


                    <?php if ($payment && $payment['payment_status'] === 'pending'): ?>

                        <div class="status-box status-pending">

                            <strong>⏳ Menunggu pembayaran</strong>

                            <br>

                            Silakan pilih metode pembayaran dan konfirmasi pembayaran kamu.

                        </div>

                    <?php endif; ?>


                    <form action="proses_pembayaran.php" method="POST">

                        <input
                            type="hidden"
                            name="auction_id"
                            value="<?= $auction_id ?>"
                        >


                        <div class="method-title">
                            Pilih Metode Pembayaran
                        </div>


                        <!-- BANK TRANSFER -->

                        <label class="method-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="Bank Transfer"
                                required
                            >

                            <div class="method-box">

                                <div class="method-icon">
                                    🏦
                                </div>

                                <div>

                                    <div class="method-name">
                                        Bank Transfer
                                    </div>

                                    <div class="method-desc">
                                        Transfer melalui rekening bank
                                    </div>

                                </div>

                            </div>

                        </label>


                        <!-- E-WALLET -->

                        <label class="method-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="E-Wallet"
                                required
                            >

                            <div class="method-box">

                                <div class="method-icon">
                                    📱
                                </div>

                                <div>

                                    <div class="method-name">
                                        E-Wallet
                                    </div>

                                    <div class="method-desc">
                                        DANA, GoPay, OVO, atau e-wallet lainnya
                                    </div>

                                </div>

                            </div>

                        </label>


                        <!-- COD -->

                        <label class="method-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="COD"
                                required
                            >

                            <div class="method-box">

                                <div class="method-icon">
                                    📦
                                </div>

                                <div>

                                    <div class="method-name">
                                        COD
                                    </div>

                                    <div class="method-desc">
                                        Bayar ketika barang diterima
                                    </div>

                                </div>

                            </div>

                        </label>


                        <div class="payment-info">

                            💡 <strong>Informasi Pembayaran</strong>

                            <br>

                            Setelah memilih metode pembayaran,
                            data pembayaran akan dikirim ke admin
                            untuk proses verifikasi.

                            <br><br>

                            Total yang harus dibayar:

                            <strong>
                                Rp <?= number_format((float)$auction['winner_bid'], 0, ',', '.') ?>
                            </strong>

                        </div>


                        <button type="submit" class="btn-pay">

                            💳 Konfirmasi Metode Pembayaran

                        </button>


                    </form>

                <?php endif; ?>


                <a href="index.php" class="btn-back">
                    ← Kembali ke Beranda
                </a>


            </div>

        </div>


    </div>

</main>

</body>
</html>
<?php

session_start();

require_once __DIR__ . "/config/koneksi.php";

/* =========================================================
   HELPER
========================================================= */

function rupiah($nominal)
{
    return 'Rp ' . number_format((float) $nominal, 0, ',', '.');
}

function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   AMBIL ID LELANG
========================================================= */

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(404);
    die("ID lelang tidak valid.");
}

/* =========================================================
   AMBIL DATA LELANG
========================================================= */

$stmt = $conn->prepare("
    SELECT 
        a.*,
        u.name AS winner_name,

        (
            SELECT COUNT(*)
            FROM bids b
            WHERE b.auction_id = a.id
        ) AS bid_count

    FROM auctions a

    LEFT JOIN users u
        ON u.id = a.winner_user_id

    WHERE a.id = ?

    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$auction = $result->fetch_assoc();

$stmt->close();

/* =========================================================
   CEK LELANG
========================================================= */

if (!$auction) {
    http_response_code(404);
    die("Lelang tidak ditemukan.");
}

/* =========================================================
   AMBIL WAKTU DARI DATABASE MYSQL
   Agar waktu frontend dan database sama.
========================================================= */

$timeResult = $conn->query("
    SELECT NOW() AS db_now
");

$timeData = $timeResult->fetch_assoc();

$dbNow = $timeData['db_now'];

/* =========================================================
   KONVERSI WAKTU
========================================================= */

$nowTimestamp   = strtotime($dbNow);
$startTimestamp = strtotime($auction['start_time']);
$endTimestamp   = strtotime($auction['end_time']);

/* =========================================================
   STATUS WAKTU LELANG
========================================================= */

$auctionStarted = $startTimestamp <= $nowTimestamp;
$auctionExpired = $endTimestamp <= $nowTimestamp;

/*
 * User hanya boleh bid jika:
 * 1. Status database = aktif
 * 2. Waktu mulai sudah lewat
 * 3. Waktu selesai belum lewat
 */

$canBid =
    $auction['status'] === 'aktif'
    && $auctionStarted
    && !$auctionExpired;

/* =========================================================
   HARGA SAAT INI
========================================================= */

$currentPrice = (float) (
    $auction['current_price']
    ?: $auction['start_price']
);

/*
 * Bid minimal harus lebih besar dari harga sekarang.
 */

$minimumBid = $currentPrice + 1;

/* =========================================================
   ERROR / SUCCESS
========================================================= */

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

/* =========================================================
   TENTUKAN PESAN STATUS
========================================================= */

$statusMessage = '';
$statusClass = 'notice';

if ($auction['status'] !== 'aktif') {

    $statusMessage = 'Lelang sudah selesai atau sudah dinonaktifkan.';

} elseif (!$auctionStarted) {

    $statusMessage =
        'Lelang belum dimulai. Lelang dimulai pada '
        . date('d M Y, H:i', $startTimestamp)
        . '.';

} elseif ($auctionExpired) {

    $statusMessage =
        'Lelang sudah berakhir pada '
        . date('d M Y, H:i', $endTimestamp)
        . '.';

}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= e($auction['title']) ?> — LelangKita
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f5f7fb;
            color: #1b222c;
        }

        a {
            text-decoration: none;
        }

        .container {
            width: min(1100px, 92%);
            margin: auto;
        }

        /* =========================
           NAVBAR
        ========================= */

        .nav {
            background: #ffffff;
            border-bottom: 1px solid #e5e9f0;
        }

        .navin {
            height: 70px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            color: #1b222c;
            font-size: 23px;
            font-weight: 900;
        }

        .logo span {
            color: #6d4aff;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .nav-links a {
            color: #606977;
            font-weight: 700;
        }

        .nav-links a:hover {
            color: #6d4aff;
        }

        /* =========================
           BUTTON
        ========================= */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            border: 0;
            border-radius: 12px;

            padding: 12px 17px;

            font-weight: 900;
            cursor: pointer;

            transition: .2s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .primary {
            background: #6d4aff;
            color: #ffffff;
        }

        .primary:hover {
            background: #5d42d8;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            padding: 45px 0;
        }

        .crumb {
            color: #7560df;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
        }

        /* =========================
           CARD
        ========================= */

        .box {
            background: #ffffff;

            border: 1px solid #e6eaf1;
            border-radius: 24px;

            box-shadow: 0 15px 40px #1d27300b;

            overflow: hidden;
        }

        /* =========================
           FOTO
        ========================= */

        .photo {
            min-height: 500px;

            background: #edf0f5;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo img {
            width: 100%;
            height: 500px;

            object-fit: cover;
        }

        .noimg {
            font-size: 110px;
        }

        /* =========================
           CONTENT
        ========================= */

        .content {
            padding: 30px;
        }

        .title {
            font-size: 38px;
            line-height: 1.1;

            margin: 15px 0 14px;
        }

        .muted {
            color: #77818f;
            line-height: 1.6;
        }

        /* =========================
           STATUS BADGE
        ========================= */

        .badge {
            display: inline-block;

            padding: 7px 11px;

            border-radius: 99px;

            font-size: 12px;
            font-weight: 900;
        }

        .badge-active {
            background: #e8faf0;
            color: #16804b;
        }

        .badge-finished {
            background: #f0f1f4;
            color: #68717d;
        }

        /* =========================
           PRICE
        ========================= */

        .pricebox {
            background: #f6f4ff;

            border: 1px solid #e4ddff;

            padding: 20px;

            border-radius: 17px;

            margin: 22px 0;
        }

        .label {
            font-size: 13px;
            color: #747b89;
            font-weight: 800;
        }

        .price {
            font-size: 30px;
            color: #5d42d8;

            font-weight: 900;

            margin-top: 4px;
        }

        /* =========================
           STAT
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .stat {
            padding: 15px;

            border: 1px solid #e5e9f0;

            border-radius: 14px;
        }

        .stat b {
            display: block;
            margin-top: 4px;
        }

        /* =========================
           FORM
        ========================= */

        .group {
            margin: 20px 0;
        }

        .input {
            width: 100%;

            padding: 14px;

            border: 1px solid #d9dee7;

            border-radius: 12px;

            font-size: 16px;

            outline: none;
        }

        .input:focus {
            border-color: #6d4aff;

            box-shadow: 0 0 0 3px #6d4aff18;
        }

        /* =========================
           MESSAGE
        ========================= */

        .error {
            background: #fff0f0;
            color: #c53232;

            padding: 12px;

            border-radius: 10px;

            margin: 18px 0;
        }

        .success {
            background: #e8faf0;
            color: #157548;

            padding: 12px;

            border-radius: 10px;

            margin: 18px 0;
        }

        .notice {
            padding: 16px;

            border-radius: 14px;

            background: #fff7df;

            color: #7a5a00;

            margin-top: 18px;

            line-height: 1.6;
        }

        .winner {
            padding: 16px;

            border-radius: 14px;

            background: #e8faf0;

            color: #157548;

            margin-top: 18px;

            line-height: 1.6;
        }

        /* =========================
           INFO WAKTU
        ========================= */

        .time-info {
            margin-top: 18px;

            padding: 14px;

            border-radius: 14px;

            background: #f7f7fb;

            border: 1px solid #e7e8ef;

            color: #606977;

            font-size: 14px;

            line-height: 1.6;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 850px) {

            .detail {
                grid-template-columns: 1fr;
            }

            .photo,
            .photo img {
                min-height: 330px;
                height: 330px;
            }

            .title {
                font-size: 30px;
            }

        }

        @media (max-width: 600px) {

            .navin {
                height: auto;

                padding: 18px 0;

                gap: 15px;

                flex-direction: column;
            }

            .nav-links {
                gap: 12px;

                flex-wrap: wrap;

                justify-content: center;
            }

            .content {
                padding: 22px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="nav">

    <div class="container navin">

        <a
            class="logo"
            href="index.php"
        >
            🔨 Lelang<span>Kita</span>
        </a>

        <div class="nav-links">

            <a href="index.php">
                Beranda
            </a>

            <?php if (isset($_SESSION['user'])): ?>

                <a href="riwayat.php">
                    Riwayat
                </a>

            <?php else: ?>

                <a href="login.php">
                    Masuk
                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">

    <div class="container">

        <div class="crumb">
            Beranda / Detail Lelang
        </div>


        <div class="detail">

            <!-- =================================================
                 FOTO PRODUK
            ================================================== -->

            <div class="box">

                <div class="photo">

                    <?php if (!empty($auction['image'])): ?>

                        <img
                            src="<?= e($auction['image']) ?>"
                            alt="<?= e($auction['title']) ?>"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='block';
                            "
                        >

                    <?php endif; ?>

                    <span
                        class="noimg"
                        style="<?= !empty($auction['image']) ? 'display:none' : '' ?>"
                    >
                        📱
                    </span>

                </div>

            </div>


            <!-- =================================================
                 INFORMASI LELANG
            ================================================== -->

            <div class="box">

                <div class="content">

                    <!-- STATUS -->

                    <span
                        class="badge <?= $canBid ? 'badge-active' : 'badge-finished' ?>"
                    >
                        ● <?= $canBid ? 'Aktif' : ucfirst(e($auction['status'])) ?>
                    </span>


                    <!-- JUDUL -->

                    <h1 class="title">
                        <?= e($auction['title']) ?>
                    </h1>


                    <!-- DESKRIPSI -->

                    <p class="muted">
                        <?= nl2br(e($auction['description'])) ?>
                    </p>


                    <!-- HARGA -->

                    <div class="pricebox">

                        <div class="label">
                            HARGA BID SAAT INI
                        </div>

                        <div class="price">
                            <?= rupiah($currentPrice) ?>
                        </div>

                    </div>


                    <!-- STATISTIK -->

                    <div class="stats">

                        <div class="stat">

                            🔨

                            <span class="label">
                                TOTAL BID
                            </span>

                            <b>
                                <?= (int) $auction['bid_count'] ?>
                                penawaran
                            </b>

                        </div>


                        <div class="stat">

                            ⏰

                            <span class="label">
                                BERAKHIR
                            </span>

                            <b>
                                <?= date(
                                    'd M Y, H:i',
                                    $endTimestamp
                                ) ?>
                            </b>

                        </div>

                    </div>


                    <!-- PESAN ERROR -->

                    <?php if ($error): ?>

                        <div class="error">
                            <?= e($error) ?>
                        </div>

                    <?php endif; ?>


                    <!-- PESAN BERHASIL -->

                    <?php if ($success === '1'): ?>

                        <div class="success">
                            ✅ Bid berhasil disimpan.
                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         FORM BID
                    ================================================== -->

                    <?php if ($canBid): ?>

                        <?php if (
                            isset($_SESSION['user'])
                            && $_SESSION['user']['role'] === 'user'
                        ): ?>

                            <form
                                action="proses_bid.php"
                                method="POST"
                            >

                                <input
                                    type="hidden"
                                    name="auction_id"
                                    value="<?= $id ?>"
                                >


                                <div class="group">

                                    <label
                                        for="bid_amount"
                                        style="
                                            font-weight:800;
                                            display:block;
                                            margin-bottom:8px;
                                        "
                                    >
                                        Nominal bid kamu
                                    </label>

                                    <input
                                        id="bid_amount"
                                        class="input"
                                        type="number"
                                        name="bid_amount"

                                        min="<?= e((string) $minimumBid) ?>"

                                        step="1000"

                                        placeholder="<?= e((string) $minimumBid) ?>"

                                        required
                                    >

                                    <small
                                        style="
                                            display:block;
                                            margin-top:8px;
                                            color:#77818f;
                                        "
                                    >
                                        Minimal bid:
                                        <strong>
                                            <?= rupiah($minimumBid) ?>
                                        </strong>
                                    </small>

                                </div>


                                <button
                                    type="submit"
                                    class="btn primary"
                                    style="width:100%;"
                                >
                                    🔨 Pasang Bid
                                </button>

                            </form>


                        <?php elseif (!isset($_SESSION['user'])): ?>

                            <a
                                class="btn primary"
                                style="width:100%;"
                                href="login.php"
                            >
                                Masuk untuk ikut lelang
                            </a>


                        <?php else: ?>

                            <div class="notice">
                                Akun admin tidak dapat mengikuti lelang.
                            </div>

                        <?php endif; ?>


                    <?php else: ?>

                        <div class="notice">

                            <?= e($statusMessage) ?>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         INFO WAKTU
                    ================================================== -->

                    <div class="time-info">

                        <strong>Informasi waktu lelang</strong>

                        <br>

                        Mulai:
                        <?= date(
                            'd M Y, H:i',
                            $startTimestamp
                        ) ?>

                        <br>

                        Berakhir:
                        <?= date(
                            'd M Y, H:i',
                            $endTimestamp
                        ) ?>

                    </div>


                    <!-- =================================================
                         PEMENANG
                    ================================================== -->

                    <?php if (
                        $auction['winner_status'] === 'ditentukan'
                        && $auction['winner_user_id']
                    ): ?>

                        <div class="winner">

                            🏆 <strong>Pemenang Lelang</strong>

                            <br>

                            Nama:
                            <strong>
                                <?= e($auction['winner_name'] ?? '') ?>
                            </strong>

                            <br>

                            Nilai pemenang:
                            <strong>
                                <?= rupiah($auction['winner_bid']) ?>
                            </strong>

                        </div>

                    <?php endif; ?>


                </div>

            </div>

        </div>

    </div>

</main>

</body>
</html>
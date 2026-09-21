<?php

session_start();

require_once __DIR__ . "/config/koneksi.php";

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function rupiah($nominal)
{
    return 'Rp ' . number_format(
        (float) $nominal,
        0,
        ',',
        '.'
    );
}

function e($text)
{
    return htmlspecialchars(
        (string) $text,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| WAKTU DATABASE
|--------------------------------------------------------------------------
| Kita menggunakan waktu dari MySQL supaya pengecekan waktu
| frontend sama dengan waktu yang digunakan database.
|--------------------------------------------------------------------------
*/

$timeQuery = $conn->query("
    SELECT NOW() AS db_now
");

$timeData = $timeQuery
    ? $timeQuery->fetch_assoc()
    : null;

$dbNow = $timeData['db_now'] ?? date('Y-m-d H:i:s');

$nowTimestamp = strtotime($dbNow);


/*
|--------------------------------------------------------------------------
| AMBIL DATA LELANG
|--------------------------------------------------------------------------
| Semua lelang dengan status aktif ditampilkan.
|
| Lelang yang belum dimulai tetap muncul.
| User baru bisa melakukan bid ketika waktunya sudah dimulai.
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        a.id,
        a.title,
        a.description,
        a.image,
        a.start_price,
        a.current_price,
        a.start_time,
        a.end_time,
        a.status,
        a.winner_user_id,
        a.winner_status,

        (
            SELECT COUNT(*)
            FROM bids b
            WHERE b.auction_id = a.id
        ) AS bid_count

    FROM auctions a

    WHERE a.status = 'aktif'

    ORDER BY
        CASE
            WHEN a.start_time <= NOW()
                 AND a.end_time > NOW()
            THEN 0

            WHEN a.start_time > NOW()
            THEN 1

            ELSE 2
        END,

        a.start_time ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>LelangKita — Lelang Handphone Second</title>


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background: #f5f7fb;

            color: #171d28;

            line-height: 1.5;
        }

        a {
            color: inherit;
            text-decoration: none;
        }


        /* =====================================================
           CONTAINER
        ===================================================== */

        .container {
            width: min(1180px, 92%);

            margin: 0 auto;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {
            position: sticky;
            top: 0;

            z-index: 100;

            background: #ffffff;

            border-bottom:
                1px solid #e8ebf1;

            box-shadow:
                0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .navbar-inner {
            min-height: 72px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 25px;
        }

        .logo {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            font-size: 24px;

            font-weight: 900;

            letter-spacing: -0.6px;
        }

        .logo-icon {
            font-size: 24px;
        }

        .logo-purple {
            color: #6d4aff;
        }

        .nav-menu {
            display: flex;

            align-items: center;

            gap: 6px;
        }

        .nav-link {
            padding: 10px 14px;

            border-radius: 10px;

            color: #5f6876;

            font-size: 15px;

            font-weight: 800;

            transition: 0.2s ease;
        }

        .nav-link:hover {
            background: #f1efff;

            color: #6247e7;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 46px;

            padding: 11px 18px;

            border: none;

            border-radius: 12px;

            font-size: 14px;

            font-weight: 900;

            cursor: pointer;

            transition:
                background 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            color: #ffffff;

            background: #6d4aff;

            box-shadow:
                0 8px 20px rgba(109, 74, 255, 0.22);
        }

        .btn-primary:hover {
            background: #5d42d8;
        }

        .btn-light {
            color: #4f5662;

            background: #eef1f6;
        }

        .btn-light:hover {
            background: #e4e7ed;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {
            padding: 75px 0;

            background:
                radial-gradient(
                    circle at 85% 20%,
                    rgba(115, 85, 255, 0.22),
                    transparent 32%
                ),

                linear-gradient(
                    135deg,
                    #171a24 0%,
                    #28213d 100%
                );

            color: #ffffff;
        }

        .hero-inner {
            display: grid;

            grid-template-columns:
                1.15fr 0.85fr;

            align-items: center;

            gap: 55px;
        }

        .hero-label {
            display: inline-flex;

            align-items: center;

            padding: 7px 12px;

            border:
                1px solid rgba(255, 255, 255, 0.15);

            border-radius: 999px;

            background:
                rgba(255, 255, 255, 0.07);

            color: #e4dfff;

            font-size: 13px;

            font-weight: 800;
        }

        .hero-title {
            margin-top: 18px;

            font-size:
                clamp(40px, 6vw, 64px);

            line-height: 1.05;

            letter-spacing: -1.8px;
        }

        .hero-title span {
            color: #a895ff;
        }

        .hero-description {
            max-width: 650px;

            margin-top: 20px;

            color: #d3d5df;

            font-size: 17px;

            line-height: 1.7;
        }

        .hero-actions {
            display: flex;

            flex-wrap: wrap;

            gap: 12px;

            margin-top: 28px;
        }

        .hero-phone {
            padding: 20px;

            border:
                1px solid rgba(255, 255, 255, 0.14);

            border-radius: 28px;

            background:
                rgba(255, 255, 255, 0.06);

            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .hero-phone-image {
            height: 300px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 20px;

            background:
                linear-gradient(
                    145deg,
                    #7560ef,
                    #272a39
                );

            font-size: 105px;
        }


        /* =====================================================
           SECTION
        ===================================================== */

        .auction-section {
            padding: 55px 0 70px;
        }

        .section-header {
            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 22px;
        }

        .section-title {
            font-size: 31px;

            line-height: 1.2;

            font-weight: 900;

            letter-spacing: -0.7px;
        }

        .section-subtitle {
            margin-top: 6px;

            color: #7b8491;

            font-size: 15px;
        }


        /* =====================================================
           INFO
        ===================================================== */

        .info-box {
            display: flex;

            align-items: flex-start;

            gap: 12px;

            margin-bottom: 25px;

            padding: 15px 17px;

            border:
                1px solid #dfd8ff;

            border-radius: 14px;

            background: #f1efff;

            color: #5946bd;

            font-size: 14px;

            line-height: 1.6;
        }

        .info-icon {
            flex-shrink: 0;

            font-size: 18px;
        }


        /* =====================================================
           GRID
        ===================================================== */

        .auction-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 22px;

            align-items: stretch;
        }


        /* =====================================================
           AUCTION CARD
        ===================================================== */

        .auction-card {
            min-width: 0;

            display: flex;

            flex-direction: column;

            overflow: hidden;

            border:
                1px solid #e6eaf0;

            border-radius: 21px;

            background: #ffffff;

            box-shadow:
                0 10px 30px rgba(29, 39, 48, 0.06);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .auction-card:hover {
            transform: translateY(-4px);

            box-shadow:
                0 18px 40px rgba(29, 39, 48, 0.12);
        }


        /* =====================================================
           IMAGE
        ===================================================== */

        .auction-image {
            position: relative;

            width: 100%;

            height: 220px;

            flex-shrink: 0;

            overflow: hidden;

            background: #eef1f6;
        }

        .auction-image img {
            width: 100%;

            height: 100%;

            display: block;

            object-fit: cover;

            transition: transform 0.3s ease;
        }

        .auction-card:hover
        .auction-image img {
            transform: scale(1.04);
        }

        .image-placeholder {
            width: 100%;

            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 65px;
        }


        /* =====================================================
           CARD BODY
        ===================================================== */

        .auction-body {
            display: flex;

            flex-direction: column;

            flex: 1;

            min-width: 0;

            padding: 20px;
        }


        /* =====================================================
           TITLE
        ===================================================== */

        .auction-title {
            min-height: 49px;

            margin-bottom: 8px;

            display: -webkit-box;

            -webkit-box-orient: vertical;

            -webkit-line-clamp: 2;

            overflow: hidden;

            color: #141b27;

            font-size: 18px;

            font-weight: 900;

            line-height: 1.35;
        }


        /* =====================================================
           PRICE
        ===================================================== */

        .auction-price {
            min-height: 56px;

            display: flex;

            align-items: flex-start;

            overflow-wrap: anywhere;

            word-break: break-word;

            color: #6045e5;

            font-size: 22px;

            font-weight: 900;

            line-height: 1.3;
        }


        /* =====================================================
           META
        ===================================================== */

        .auction-meta {
            min-height: 34px;

            display: grid;

            grid-template-columns:
                1fr 1fr;

            align-items: center;

            gap: 8px;

            margin: 7px 0 12px;

            color: #7a8390;

            font-size: 13px;
        }

        .auction-meta span {
            min-width: 0;

            display: flex;

            align-items: center;

            gap: 4px;

            overflow: hidden;

            white-space: nowrap;

            text-overflow: ellipsis;
        }

        .auction-meta span:last-child {
            justify-content: flex-end;
        }


        /* =====================================================
           BADGE
        ===================================================== */

        .status-badge {
            width: fit-content;

            min-height: 28px;

            display: inline-flex;

            align-items: center;

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 900;
        }

        .status-active {
            background: #e8faf0;

            color: #15834c;
        }

        .status-upcoming {
            background: #fff4dc;

            color: #a86600;
        }

        .status-ended {
            background: #f0f1f4;

            color: #68717d;
        }


        /* =====================================================
           START INFORMATION
        ===================================================== */

        .start-info {
            min-height: 66px;

            margin-top: 12px;

            padding: 10px 12px;

            border-radius: 12px;

            background: #fff8e8;

            color: #8b6500;

            font-size: 12px;

            line-height: 1.5;
        }

        .start-info strong {
            font-weight: 900;
        }


        /* =====================================================
           DETAIL BUTTON
        ===================================================== */

        .detail-button {
            width: 100%;

            min-height: 50px;

            margin-top: auto;
            margin-bottom: 0;

            flex-shrink: 0;
        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-state {
            padding: 60px 25px;

            text-align: center;

            border:
                1px dashed #d8dde6;

            border-radius: 20px;

            background: #ffffff;
        }

        .empty-icon {
            margin-bottom: 10px;

            font-size: 55px;
        }

        .empty-state h3 {
            margin-bottom: 6px;

            font-size: 22px;
        }

        .empty-state p {
            color: #7b8491;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .footer {
            padding: 35px 0;

            background: #171b24;

            color: #aeb5c1;

            font-size: 14px;
        }

        .footer strong {
            color: #ffffff;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1100px) {

            .auction-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

        }


        /* =====================================================
           TABLET KECIL
        ===================================================== */

        @media (max-width: 850px) {

            .hero {
                padding: 55px 0;
            }

            .hero-inner {
                grid-template-columns: 1fr;

                gap: 35px;
            }

            .hero-phone-image {
                height: 250px;
            }

            .auction-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .container {
                width: 92%;
            }

            .navbar-inner {
                min-height: 65px;
            }

            .logo {
                font-size: 21px;
            }

            .logo-icon {
                font-size: 21px;
            }

            .nav-link {
                padding: 8px 9px;

                font-size: 13px;
            }

            .nav-menu {
                gap: 2px;
            }

            .hero-title {
                font-size: 40px;

                letter-spacing: -1px;
            }

            .hero-description {
                font-size: 15px;
            }

            .hero-phone {
                padding: 14px;
            }

            .hero-phone-image {
                height: 210px;

                font-size: 80px;
            }

            .auction-section {
                padding: 40px 0 55px;
            }

            .section-title {
                font-size: 26px;
            }

            .auction-grid {
                grid-template-columns: 1fr;

                gap: 18px;
            }

            .auction-image {
                height: 240px;
            }

            .auction-title {
                min-height: auto;
            }

            .auction-price {
                min-height: auto;

                font-size: 21px;

                margin-bottom: 6px;
            }

            .auction-meta {
                margin-top: 5px;
            }

        }


        /* =====================================================
           MOBILE KECIL
        ===================================================== */

        @media (max-width: 420px) {

            .nav-link:not(.btn-light) {
                display: none;
            }

            .hero-title {
                font-size: 35px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar">

    <div class="container navbar-inner">

        <a
            href="index.php"
            class="logo"
        >

            <span class="logo-icon">🔨</span>

            <span>
                Lelang<span class="logo-purple">Kita</span>
            </span>

        </a>


        <div class="nav-menu">

            <a
                href="index.php"
                class="nav-link"
            >
                Beranda
            </a>

            <a
                href="#lelang"
                class="nav-link"
            >
                Lelang
            </a>


            <?php if (isset($_SESSION['user'])): ?>

                <a
                    href="riwayat.php"
                    class="nav-link"
                >
                    Riwayat
                </a>

                <a
                    href="logout.php"
                    class="btn btn-light"
                >
                    Keluar
                </a>

            <?php else: ?>

                <a
                    href="login.php"
                    class="nav-link"
                >
                    Masuk
                </a>

                <a
                    href="register.php"
                    class="btn btn-primary"
                >
                    Daftar
                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>


<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">

    <div class="container hero-inner">


        <div class="hero-content">

            <span class="hero-label">
                🔥 Lelang Handphone Second
            </span>


            <h1 class="hero-title">

                Temukan HP impianmu,

                <br>

                <span>menangkan lelangnya.</span>

            </h1>


            <p class="hero-description">

                Ikuti lelang handphone second dengan mudah.
                Pantau harga, pasang penawaran terbaik,
                dan menangkan perangkat favoritmu.

            </p>


            <div class="hero-actions">

                <a
                    href="#lelang"
                    class="btn btn-primary"
                >
                    Lihat Lelang →
                </a>


                <?php if (!isset($_SESSION['user'])): ?>

                    <a
                        href="register.php"
                        class="btn btn-light"
                    >
                        Buat Akun
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <div class="hero-phone">

            <div class="hero-phone-image">
                📱
            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     DAFTAR LELANG
===================================================== -->

<section
    class="auction-section"
    id="lelang"
>

    <div class="container">


        <!-- HEADER -->

        <div class="section-header">

            <div>

                <h2 class="section-title">
                    Lelang Handphone
                </h2>

                <p class="section-subtitle">
                    Pilih produk dan ikuti penawaran sekarang.
                </p>

            </div>

        </div>


        <!-- INFO -->

        <div class="info-box">

            <span class="info-icon">
                📢
            </span>

            <span>
                Produk yang ditambahkan admin akan langsung
                muncul di halaman ini. Jika jadwalnya belum
                dimulai, produk akan ditandai
                <strong>Akan Dimulai</strong>.
            </span>

        </div>


        <!-- =================================================
             PRODUK
        ================================================== -->

        <?php if ($result && $result->num_rows > 0): ?>

            <div class="auction-grid">


                <?php while ($auction = $result->fetch_assoc()): ?>


                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | WAKTU LELANG
                    |--------------------------------------------------------------------------
                    */

                    $startTimestamp = strtotime(
                        $auction['start_time']
                    );

                    $endTimestamp = strtotime(
                        $auction['end_time']
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | STATUS WAKTU
                    |--------------------------------------------------------------------------
                    */

                    $isStarted =
                        $startTimestamp <= $nowTimestamp;

                    $isExpired =
                        $endTimestamp <= $nowTimestamp;


                    /*
                    |--------------------------------------------------------------------------
                    | BISA BID
                    |--------------------------------------------------------------------------
                    */

                    $canBid =
                        $auction['status'] === 'aktif'
                        && $isStarted
                        && !$isExpired;


                    /*
                    |--------------------------------------------------------------------------
                    | AKAN DIMULAI
                    |--------------------------------------------------------------------------
                    */

                    $isUpcoming =
                        $auction['status'] === 'aktif'
                        && !$isStarted;


                    /*
                    |--------------------------------------------------------------------------
                    | HARGA
                    |--------------------------------------------------------------------------
                    */

                    $displayPrice =
                        $auction['current_price']
                        ?: $auction['start_price'];

                    ?>


                    <!-- =================================================
                         CARD
                    ================================================== -->

                    <article class="auction-card">


                        <!-- GAMBAR -->

                        <div class="auction-image">


                            <?php if (!empty($auction['image'])): ?>

                                <img
                                    src="<?= e($auction['image']) ?>"
                                    alt="<?= e($auction['title']) ?>"

                                    onerror="
                                        this.style.display='none';
                                        this.nextElementSibling.style.display='flex';
                                    "
                                >

                                <div
                                    class="image-placeholder"
                                    style="display: none;"
                                >
                                    📱
                                </div>


                            <?php else: ?>

                                <div class="image-placeholder">
                                    📱
                                </div>

                            <?php endif; ?>


                        </div>


                        <!-- BODY -->

                        <div class="auction-body">


                            <!-- JUDUL -->

                            <h3 class="auction-title">
                                <?= e($auction['title']) ?>
                            </h3>


                            <!-- HARGA -->

                            <div class="auction-price">
                                <?= rupiah($displayPrice) ?>
                            </div>


                            <!-- META -->

                            <div class="auction-meta">

                                <span title="Jumlah penawaran">

                                    🔨

                                    <?= (int) $auction['bid_count'] ?>

                                    bid

                                </span>


                                <span
                                    title="Waktu berakhir"
                                >

                                    ⏰

                                    <?= date(
                                        'd M H:i',
                                        $endTimestamp
                                    ) ?>

                                </span>

                            </div>


                            <!-- STATUS -->

                            <?php if ($canBid): ?>

                                <span
                                    class="status-badge status-active"
                                >
                                    ● Aktif
                                </span>


                            <?php elseif ($isUpcoming): ?>

                                <span
                                    class="status-badge status-upcoming"
                                >
                                    ● Akan Dimulai
                                </span>


                            <?php else: ?>

                                <span
                                    class="status-badge status-ended"
                                >
                                    ● Jadwal Berakhir
                                </span>

                            <?php endif; ?>


                            <!-- INFO MULAI -->

                            <?php if ($isUpcoming): ?>

                                <div class="start-info">

                                    ⏳

                                    <strong>
                                        Mulai:
                                    </strong>

                                    <br>

                                    <?= date(
                                        'd M Y, H:i',
                                        $startTimestamp
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <!-- TOMBOL DETAIL -->

                            <a
                                href="detail.php?id=<?= (int) $auction['id'] ?>"
                                class="btn btn-primary detail-button"
                            >
                                Lihat Detail
                            </a>


                        </div>

                    </article>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <!-- =================================================
                 BELUM ADA LELANG
            ================================================== -->

            <div class="empty-state">

                <div class="empty-icon">
                    📭
                </div>

                <h3>
                    Belum Ada Lelang
                </h3>

                <p>
                    Admin belum menambahkan produk lelang.
                </p>

            </div>

        <?php endif; ?>


    </div>

</section>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">

    <div class="container">

        <strong>
            🔨 LelangKita
        </strong>

        — Platform lelang handphone second.

    </div>

</footer>


</body>

</html>
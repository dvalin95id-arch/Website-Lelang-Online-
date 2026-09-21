<?php

session_start();

require_once __DIR__ . "/config/koneksi.php";


/* =========================================================
   CEK LOGIN USER
========================================================= */

if (
    !isset($_SESSION['user'])
    || $_SESSION['user']['role'] !== 'user'
) {

    header(
        "Location: login.php?error="
        . urlencode("Silakan login sebagai pengguna terlebih dahulu.")
    );

    exit;
}


/* =========================================================
   AMBIL DATA POST
========================================================= */

$auctionId = (int) ($_POST['auction_id'] ?? 0);

$bidAmount = (float) ($_POST['bid_amount'] ?? 0);

$userId = (int) ($_SESSION['user']['id'] ?? 0);


/* =========================================================
   VALIDASI
========================================================= */

if ($auctionId <= 0 || $userId <= 0) {

    header(
        "Location: index.php?error="
        . urlencode("Data lelang tidak valid.")
    );

    exit;
}


if ($bidAmount <= 0) {

    header(
        "Location: detail.php?id="
        . $auctionId
        . "&error="
        . urlencode("Nominal bid tidak valid.")
    );

    exit;
}


/* =========================================================
   MULAI TRANSAKSI
========================================================= */

$conn->begin_transaction();


try {

    /* =====================================================
       AMBIL DATA LELANG
       FOR UPDATE mencegah bentrok ketika ada bid bersamaan
    ====================================================== */

    $stmt = $conn->prepare("
        SELECT
            id,
            status,
            start_time,
            end_time,
            start_price,
            current_price
        FROM auctions
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param(
        "i",
        $auctionId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $auction = $result->fetch_assoc();

    $stmt->close();


    /* =====================================================
       CEK LELANG
    ====================================================== */

    if (!$auction) {

        throw new Exception(
            "Lelang tidak ditemukan."
        );

    }


    /* =====================================================
       CEK STATUS
    ====================================================== */

    if ($auction['status'] !== 'aktif') {

        throw new Exception(
            "Lelang sudah selesai atau dinonaktifkan."
        );

    }


    /* =====================================================
       CEK WAKTU MENGGUNAKAN MYSQL
    ====================================================== */

    $timeResult = $conn->query("
        SELECT
            NOW() AS db_now
    ");

    $timeData = $timeResult->fetch_assoc();

    $now = strtotime(
        $timeData['db_now']
    );

    $startTime = strtotime(
        $auction['start_time']
    );

    $endTime = strtotime(
        $auction['end_time']
    );


    /* =====================================================
       BELUM DIMULAI
    ====================================================== */

    if ($now < $startTime) {

        throw new Exception(
            "Lelang belum dimulai."
        );

    }


    /* =====================================================
       SUDAH BERAKHIR
    ====================================================== */

    if ($now >= $endTime) {

        throw new Exception(
            "Lelang sudah berakhir."
        );

    }


    /* =====================================================
       HITUNG MINIMAL BID
    ====================================================== */

    $currentPrice = (float) (
        $auction['current_price']
        ?: $auction['start_price']
    );

    $minimumBid = $currentPrice + 1;


    /* =====================================================
       CEK NOMINAL BID
    ====================================================== */

    if ($bidAmount < $minimumBid) {

        throw new Exception(
            "Bid minimal Rp "
            . number_format(
                $minimumBid,
                0,
                ',',
                '.'
            )
        );

    }


    /* =====================================================
       SIMPAN BID
    ====================================================== */

    $stmt = $conn->prepare("
        INSERT INTO bids
        (
            auction_id,
            user_id,
            bid_amount,
            bid_time,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NOW(),
            NOW()
        )
    ");

    $stmt->bind_param(
        "iid",
        $auctionId,
        $userId,
        $bidAmount
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "Bid gagal disimpan."
        );

    }

    $stmt->close();


    /* =====================================================
       UPDATE HARGA TERKINI
    ====================================================== */

    $stmt = $conn->prepare("
        UPDATE auctions
        SET current_price = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "di",
        $bidAmount,
        $auctionId
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "Harga lelang gagal diperbarui."
        );

    }

    $stmt->close();


    /* =====================================================
       SIMPAN TRANSAKSI
    ====================================================== */

    $conn->commit();


    /* =====================================================
       KEMBALI KE DETAIL
    ====================================================== */

    header(
        "Location: detail.php?id="
        . $auctionId
        . "&success=1"
    );

    exit;


} catch (Exception $e) {

    /* =====================================================
       BATALKAN TRANSAKSI
    ====================================================== */

    $conn->rollback();


    /* =====================================================
       KEMBALI DENGAN PESAN ERROR
    ====================================================== */

    header(
        "Location: detail.php?id="
        . $auctionId
        . "&error="
        . urlencode($e->getMessage())
    );

    exit;

}
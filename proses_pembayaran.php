<?php

session_start();

require_once "config/koneksi.php";


/* =========================
   CEK LOGIN
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
   AMBIL DATA FORM
========================= */

$auction_id = (int)($_POST['auction_id'] ?? 0);

$payment_method = trim($_POST['payment_method'] ?? '');


/* =========================
   VALIDASI
========================= */

$allowed_methods = [
    'Bank Transfer',
    'E-Wallet',
    'COD'
];


if ($auction_id <= 0 || !in_array($payment_method, $allowed_methods, true)) {

    die("Data pembayaran tidak valid.");

}


/* =========================
   CEK LELANG + PEMENANG
========================= */

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        winner_user_id,
        winner_bid,
        status,
        winner_status
    FROM auctions
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $auction_id);

$stmt->execute();

$auction = $stmt->get_result()->fetch_assoc();


if (!$auction) {

    die("Data lelang tidak ditemukan.");

}


/* =========================
   CEK PEMENANG
========================= */

if ((int)$auction['winner_user_id'] !== $user_id) {

    die("Anda bukan pemenang lelang ini.");

}


/* =========================
   CEK STATUS LELANG
========================= */

if ($auction['status'] !== 'selesai') {

    die("Lelang belum selesai.");

}


/* =========================
   CEK PEMENANG SUDAH DITENTUKAN
========================= */

if ($auction['winner_status'] !== 'ditentukan') {

    die("Pemenang belum ditentukan oleh sistem.");

}


/* =========================
   CEK PAYMENT LAMA
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
   JIKA SUDAH PAID
========================= */

if ($payment && $payment['payment_status'] === 'paid') {

    header("Location: pembayaran.php?id=" . $auction_id);
    exit;

}


/* =========================
   UPDATE PAYMENT
========================= */

if ($payment) {

    $stmt = $conn->prepare("
        UPDATE payments
        SET
            amount = ?,
            payment_method = ?,
            payment_status = 'pending'
        WHERE id = ?
    ");

    $amount = (float)$auction['winner_bid'];

    $stmt->bind_param(
        "dsi",
        $amount,
        $payment_method,
        $payment['id']
    );

    $stmt->execute();


} else {


    /* =========================
       BUAT PAYMENT BARU
    ========================= */

    $stmt = $conn->prepare("
        INSERT INTO payments
        (
            auction_id,
            user_id,
            amount,
            payment_method,
            payment_status,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'pending',
            NOW()
        )
    ");

    $amount = (float)$auction['winner_bid'];

    $stmt->bind_param(
        "iids",
        $auction_id,
        $user_id,
        $amount,
        $payment_method
    );

    $stmt->execute();

}


/* =========================
   SELESAI
========================= */

header("Location: pembayaran.php?id=" . $auction_id);
exit;

?>
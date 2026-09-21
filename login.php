<?php
session_start();
if(isset($_SESSION['user'])) { header("Location: index.php"); exit; }
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk — LelangKita</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Segoe UI,Arial;background:linear-gradient(135deg,#151823,#302653);min-height:100vh;display:grid;place-items:center;color:#202631}.wrap{width:min(430px,92%)}.logo{text-align:center;color:#fff;font-size:27px;font-weight:900;margin-bottom:22px}.logo span{color:#a995ff}.card{background:#fff;border-radius:26px;padding:32px;box-shadow:0 25px 70px #0005}.back{color:#6d4aff;font-weight:800}.h{margin:22px 0 5px;font-size:30px}.muted{color:#7a8390}.group{margin:18px 0}.group label{display:block;font-weight:800;margin-bottom:8px}.input{width:100%;padding:14px;border:1px solid #d9dee7;border-radius:12px;font-size:15px;outline:none}.input:focus{border-color:#7659ee;box-shadow:0 0 0 4px #7659ee14}.btn{width:100%;border:0;border-radius:12px;padding:14px;background:#6d4aff;color:#fff;font-weight:900;font-size:15px;cursor:pointer}.error{background:#fff0f0;color:#c53232;padding:12px;border-radius:10px;margin:15px 0}.bottom{text-align:center;margin-top:20px}.bottom a{color:#6546e9;font-weight:800}
</style></head><body><div class="wrap"><div class="logo">🔨 Lelang<span>Kita</span></div><div class="card"><a class="back" href="index.php">← Kembali ke beranda</a><h1 class="h">Selamat datang 👋</h1><p class="muted">Masuk untuk mengikuti lelang.</p>
<?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form action="proses_login.php" method="POST"><div class="group"><label>Username</label><input class="input" name="username" required autocomplete="username"></div><div class="group"><label>Password</label><input class="input" type="password" name="password" required autocomplete="current-password"></div><button class="btn">Masuk ke Lelang →</button></form>
<div class="bottom">Belum punya akun? <a href="register.php">Daftar sekarang</a></div></div></div></body></html>
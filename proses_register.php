<?php
require_once __DIR__ . "/config/koneksi.php";
$name=trim($_POST['name']??''); $username=trim($_POST['username']??''); $email=trim($_POST['email']??'');
$password=$_POST['password']??''; $confirm=$_POST['password_confirmation']??'';
if($name===''||$username===''||$email===''||$password===''){header("Location:register.php?error=".urlencode("Semua data wajib diisi."));exit;}
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){header("Location:register.php?error=".urlencode("Format email tidak valid."));exit;}
if($password!==$confirm){header("Location:register.php?error=".urlencode("Konfirmasi password tidak sama."));exit;}
$stmt=$conn->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");$stmt->bind_param("ss",$username,$email);$stmt->execute();
if($stmt->get_result()->num_rows){header("Location:register.php?error=".urlencode("Username atau email sudah digunakan."));exit;}
$hash=password_hash($password,PASSWORD_BCRYPT);$role='user';
$stmt=$conn->prepare("INSERT INTO users(name,username,email,password,role) VALUES(?,?,?,?,?)");$stmt->bind_param("sssss",$name,$username,$email,$hash,$role);
if(!$stmt->execute()){header("Location:register.php?error=".urlencode("Pendaftaran gagal: ".$conn->error));exit;}
header("Location:login.php?error=".urlencode("Akun berhasil dibuat. Silakan masuk."));exit;
?>
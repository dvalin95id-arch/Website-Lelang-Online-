<?php
session_start();
require_once __DIR__ . "/config/koneksi.php";
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
if($username==='' || $password===''){ header("Location: login.php?error=".urlencode("Username dan password wajib diisi.")); exit; }
$stmt=$conn->prepare("SELECT id,name,username,email,password,role FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s",$username); $stmt->execute(); $user=$stmt->get_result()->fetch_assoc();
if(!$user || !password_verify($password,$user['password'])){ header("Location: login.php?error=".urlencode("Username atau password salah.")); exit; }
session_regenerate_id(true);
$_SESSION['user']=$user;
if($user['role']==='admin'){ header("Location: admin/dashboard.php"); } else { header("Location: index.php"); }
exit;
?>
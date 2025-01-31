<?php
// Di /sistem/admin/auth/logout.php
session_start();
require_once '../../classes/User.php';

// Redirect ke halaman login admin
$redirect_to = User::getLoginPath(User::getRoles()['ADMIN']);

// Hapus semua session
session_destroy();

// Redirect
header("Location: " . $redirect_to);
exit();

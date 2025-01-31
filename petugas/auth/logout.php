<?php
// Di /sistem/petugas/auth/logout.php
session_start();
require_once '../../classes/User.php';

// Redirect ke halaman login staff
$redirect_to = User::getLoginPath(User::getRoles()['STAFF']);

// Hapus semua session
session_destroy();

// Redirect
header("Location: " . $redirect_to);
exit();

<?php
session_start();
session_unset();  // Menghapus semua variabel sesi
session_destroy();  // Menghancurkan sesi

// Arahkan pengguna kembali ke halaman login
header("Location: login-page.php");
exit();
?>

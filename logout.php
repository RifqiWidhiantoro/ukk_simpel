<?php
session_start();
if (isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'] === true) {
    $conn = new mysqli("localhost", "root", "", "todo_list");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    $user_id = $_SESSION['user_id'];

    // Delete only tasks and categories created by the anonymous account
    $conn->query("DELETE FROM tasks WHERE user_id = $user_id");
    $conn->query("DELETE FROM categories WHERE user_id = $user_id AND role != 'admin'"); // Prevent deletion of admin categories

    // Delete the anonymous user account
    $conn->query("DELETE FROM users WHERE id = $user_id");

    $conn->close();
}

if ($_SESSION['role'] !== 'anonymous') {
    session_unset();  // Menghapus semua variabel sesi
}
session_destroy();  // Menghancurkan sesi

header("Location: home.php");
exit();
?>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login-page.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "todo_list");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$title = $_POST['title'];
$description = $_POST['description'];
$priority = $_POST['priority'];
$category_id = $_POST['category_id'];
$due_date = $_POST['due_date'];
$user_id = $_SESSION['user_id'];

// Debugging: Check if user_id is set in the session
if (empty($user_id)) {
    echo "<script>alert('User ID tidak ditemukan di sesi!'); window.history.back();</script>";
    exit();
}

// Validasi tanggal
$current_date = date('Y-m-d');
if ($due_date < $current_date) {
    echo "<script>alert('Tanggal jatuh tempo tidak boleh kurang dari hari ini!'); window.history.back();</script>";
    exit();
}

// Validasi user_id
$user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
$user_check->bind_param("i", $user_id);
$user_check->execute();
$user_check->store_result();
if ($user_check->num_rows == 0) {
    echo "<script>alert('User tidak ditemukan!'); window.history.back();</script>";
    exit();
}
$user_check->close();

// Proses penyimpanan ke database
$stmt = $conn->prepare("INSERT INTO tasks (title, description, priority, category_id, due_date, user_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $title, $description, $priority, $category_id, $due_date, $user_id);
if ($stmt->execute()) {
    header("Location: dashboard.php");
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();

$conn->close();
?>

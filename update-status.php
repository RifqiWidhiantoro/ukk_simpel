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

if (isset($_GET['id'])) {
    $task_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Cek status saat ini
    $stmt = $conn->prepare("SELECT status FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    if ($task) {
        $current_status = $task['status'];

        // Tentukan status selanjutnya
        if ($current_status == 'pending') {
            $new_status = 'progress';
        } elseif ($current_status == 'progress') {
            $new_status = 'completed';
        } else {
            // Jika sudah completed, tidak ada perubahan
            $new_status = $current_status;
        }

        // Update status di database
        $update_stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("sii", $new_status, $task_id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    $stmt->close();
}

$conn->close();
header("Location: dashboard.php");
exit();
?>

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $user_id = $_SESSION['user_id'];

    // Get the profile photo path
    $result = $conn->query("SELECT profile_photo FROM users WHERE id = $user_id");
    $user = $result->fetch_assoc();
    $profile_photo = $user['profile_photo'];

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);

    if ($stmt->execute()) {
        // Delete the profile photo if it's not the default one
        if ($profile_photo != 'pictures/default.png' && file_exists($profile_photo)) {
            unlink($profile_photo);
        }
        // Redirect kembali ke dashboard setelah penghapusan
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

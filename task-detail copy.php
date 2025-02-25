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

$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Query detail tugas
$sql = "SELECT t.*, c.name AS category_name
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.id = $task_id
          AND t.user_id = $user_id
        LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows < 1) {
    echo "Tugas tidak ditemukan atau Anda tidak berhak mengaksesnya.";
    exit();
}

$task = $result->fetch_assoc();

// Handle aksi (opsional)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Contoh aksi hapus
    if (isset($_POST['delete'])) {
        $conn->query("DELETE FROM tasks WHERE id = $task_id AND user_id = $user_id");
        header("Location: dashboard.php");
        exit();
    }
    // Contoh aksi update status
    if (isset($_POST['complete'])) {
        $conn->query("UPDATE tasks SET status = 'completed' WHERE id = $task_id AND user_id = $user_id");
        header("Location: dashboard.php");
        exit();
    }
    // dsb.
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Tugas</title>
    <style>
        body {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            font-family: Arial, sans-serif;
            color: #fff;
            padding: 20px;
        }
        .detail-container {
            max-width: 480px;
            margin: auto;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 20px;
        }
        .actions {
            margin-top: 20px;
            display: flex; 
            gap: 10px;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .delete-btn {
            background: #e74c3c; 
            color: #fff;
        }
        .complete-btn {
            background: #28a745; 
            color: #fff;
        }
        .edit-btn, .share-btn {
            background: #fff; 
            color: #333;
        }
    </style>
</head>
<body>
    <div class="detail-container">
        <h2><?= htmlspecialchars($task['title']) ?></h2>
        <p><strong>Deskripsi:</strong><br><?= nl2br(htmlspecialchars($task['description'])) ?></p>
        <p><strong>Prioritas:</strong> <?= htmlspecialchars($task['priority']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($task['status']) ?></p>
        <p><strong>Kategori:</strong> <?= $task['category_name'] ?? 'Tidak ada kategori' ?></p>
        <p><strong>Due Date:</strong> <?= htmlspecialchars($task['due_date']) ?></p>

        <div class="actions">
            <!-- Edit: Arahkan ke halaman edit-task.php?id=... -->
            <a href="edit-task.php?id=<?= $task['id'] ?>" class="edit-btn" style="text-decoration:none; padding:10px; background:#fff; color:#333; border-radius:5px;">Edit</a>
            <!-- Share: Bisa link atau copy ke clipboard, dsb. -->
            <button class="share-btn">Share</button>
            <!-- Complete: update status ke 'completed' -->
            <?php if ($task['status'] !== 'completed'): ?>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="complete" class="complete-btn">Complete</button>
                </form>
            <?php endif; ?>
            <!-- Delete: hapus tugas -->
            <form method="POST" style="display:inline;">
                <button type="submit" name="delete" class="delete-btn" onclick="return confirm('Hapus tugas ini?')">Delete</button>
            </form>
        </div>
    </div>
</body>
</html>

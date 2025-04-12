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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    $category_id = trim($_POST['category_id']);
    $due_date = trim($_POST['due_date']);
    $status = trim($_POST['status']);

    // Restrict future status changes
    $current_task = $conn->query("SELECT status FROM tasks WHERE id = $task_id")->fetch_assoc();
    $current_status = $current_task['status'];

    if (
        ($current_status === 'pending' && $status !== 'pending') ||
        ($current_status === 'progress' && $status === 'completed')
    ) {
        echo "Error: You cannot change the status to a future status.";
        exit();
    }

    $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, category_id = ?, due_date = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssissi", $title, $description, $priority, $category_id, $due_date, $status, $task_id);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    $task_id = $_GET['id'];
    $result = $conn->query("SELECT * FROM tasks WHERE id = $task_id");
    $task = $result->fetch_assoc();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tugas - To-Do List</title>
    <style>
        body {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .dashboard-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .task-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .task-form input, 
        .task-form textarea, 
        .task-form select, 
        .task-form button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .task-form button {
            background: linear-gradient(135deg, #3B82F6, #1E3A8A);
            color: white;
            cursor: pointer;
            border: none;
            transition: background 0.3s;
        }
        .task-form button:hover {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
        }
        .button-link {
        display: block;
        width: 100%;
        padding: 10px;
        background: linear-gradient(135deg, #3B82F6, #1E3A8A);
        color: white;
        text-align: center;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
        font-weight: normal;
        line-height: normal;
        border: none;
        margin-top: 10px;
        box-sizing: border-box;
        cursor: pointer;
        font-family: Arial, sans-serif;
        }

        .button-link:hover {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
        }

    </style>
</head>
<body>
    <div class="dashboard-container">
        <h2>Edit Tugas</h2>
        <form class="task-form" action="edit-task.php" method="POST">
            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
            <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" placeholder="Judul Tugas" required>
            <textarea name="description" placeholder="Deskripsi" required><?= htmlspecialchars($task['description']) ?></textarea>
            <select name="priority" required>
                <option value="high" <?= $task['priority'] == 'high' ? 'selected' : '' ?>>Tinggi</option>
                <option value="medium" <?= $task['priority'] == 'medium' ? 'selected' : '' ?>>Sedang</option>
                <option value="low" <?= $task['priority'] == 'low' ? 'selected' : '' ?>>Rendah</option>
            </select>
            <select name="category_id" required>
                <option value="">Pilih Kategori</option>
                <?php while ($category = $categories->fetch_assoc()): ?>
                    <option value="<?= $category['id'] ?>" <?= $task['category_id'] == $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="date" name="due_date" value="<?= htmlspecialchars($task['due_date']) ?>" min="<?= date('Y-m-d'); ?>" required>
            <select name="status" required>
                <option value="pending" <?= $task['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="progress" <?= $task['status'] == 'progress' ? 'selected' : '' ?>>Progress</option>
                <option value="completed" <?= $task['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <button type="submit">Update Tugas</button>
        </form>
        <a href="details.php?id=<?= $task['id'] ?>" class="button-link">Detail Tugas</a>
    </div>
</body>
</html>

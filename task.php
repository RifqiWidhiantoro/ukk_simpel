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
$message = "";

// Proses tambah tugas
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = isset($_POST['title']) ? $conn->real_escape_string($_POST['title']) : '';
    $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
    $priority = isset($_POST['priority']) ? $conn->real_escape_string($_POST['priority']) : 'medium';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : NULL;
    $due_date = isset($_POST['due_date']) ? $conn->real_escape_string($_POST['due_date']) : NULL;

    if (!empty($title) && !empty($due_date)) {
        $sql = "INSERT INTO tasks (user_id, title, description, priority, category_id, due_date) 
                VALUES ($user_id, '$title', '$description', '$priority', $category_id, '$due_date')";
        if ($conn->query($sql) === TRUE) {
            $message = "Tugas berhasil ditambahkan.";
            echo "<script>
                    alert('Tugas berhasil ditambahkan.');
                    window.location.href = 'dashboard.php';
                  </script>";
            exit();
        } else {
            $message = "Error: " . $conn->error;
        }
    } else {
        $message = "Judul dan tanggal jatuh tempo harus diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Tugas - To-Do List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .task-container {
            max-width: 400px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input[type="text"], 
        textarea, 
        select, 
        input[type="date"],
        button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            color: white;
            cursor: pointer;
        }
        button:hover {
            opacity: 0.9;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            text-decoration: none;
            color: #6c757d;
        }
        .back-link:hover {
            color: #9b59b6;
        }
    </style>
</head>
<body>
    <div class="task-container">
        <h2>Tambah Tugas</h2>
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="title" placeholder="Judul Tugas" required>
            <textarea name="description" placeholder="Deskripsi" required></textarea>
            <select name="priority" required>
                <option value="high">Prioritas Tinggi</option>
                <option value="medium">Prioritas Sedang</option>
                <option value="low">Prioritas Rendah</option>
            </select>
            <select name="category_id">
                <option value="">Pilih Kategori</option>
                <?php
                $cat_sql = "SELECT * FROM categories WHERE user_id = $user_id OR role = 'admin' ORDER BY name ASC";
                $categories = $conn->query($cat_sql);
                while ($category = $categories->fetch_assoc()):
                ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <input type="date" name="due_date" required>
            <button type="submit">Tambah Tugas</button>
        </form>
        <a href="dashboard.php" class="back-link"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
    </div>
</body>
</html>
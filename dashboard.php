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
$result = $conn->query("SELECT * FROM tasks WHERE user_id = $user_id 
    ORDER BY 
        CASE 
            WHEN priority = 'high' THEN 1
            WHEN priority = 'medium' THEN 2
            WHEN priority = 'low' THEN 3
            ELSE 4
        END, 
        status ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - To-Do List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            padding: 20px;
        }
        .dashboard-container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2, h3 {
            text-align: center;
            margin-bottom: 20px;
        }
        .task-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        .task-form input, .task-form textarea, .task-form select, .task-form button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .task-form button {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            color: white;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        .actions a, 
        .actions button {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 20px;
            padding: 5px;
            margin: 0 2px;
            color: #6c757d;
            transition: color 0.3s;
        }

        .actions a:hover, 
        .actions button:hover {
            color: #9b59b6;
        }

        .edit { background: #ffc107; }
        .delete { background: #dc3545; color: white; }
        .done { background: #28a745; color: white; }
        .due-danger {
            background-color: rgba(255, 0, 0, 0.1); /* Light Red */
        }
        .due-warning {
            background-color: rgba(255, 165, 0, 0.1); /* Light Orange */
        }
        .due-safe {
            background-color: rgba(0, 123, 255, 0.1); /* Light Blue */
        }
        .status-completed {
            background-color: rgba(40, 167, 69, 0.1); /* Light Green */
        }
        @media (max-width: 600px) {
            .dashboard-container {
                padding: 10px;
                max-width: 100%;
            }
            .task-form input, .task-form textarea, .task-form select, .task-form button {
                padding: 8px;
            }
            table, th, td {
                padding: 8px;
            }
            .actions a, .actions button {
                padding: 5px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h2>Dashboard</h2>
            <div>
                <a href="profile.php">Profil</a>
                <a href="manage-categories.php">Manage Categories</a>
            </div>
        </header>

        <h3>Tambah Tugas</h3>
        <form class="task-form" action="add-task.php" method="POST">
            <input type="text" name="title" placeholder="Judul Tugas" required>
            <textarea name="description" placeholder="Deskripsi" required></textarea>
            <select name="priority">
                <option value="high">Tinggi</option>
                <option value="medium">Sedang</option>
                <option value="low">Rendah</option>
            </select>
            <select name="category_id">
                <option value="">Pilih Kategori</option>
                <?php
                $categories = $conn->query("SELECT * FROM categories WHERE user_id = $user_id OR role = 'admin' ORDER BY name ASC");
                while ($category = $categories->fetch_assoc()): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <input type="date" name="due_date" min="<?= date('Y-m-d'); ?>" placeholder="Tanggal Jatuh Tempo" required>
            <button type="submit">Tambah</button>
        </form>

        <h3>Daftar Tugas</h3>
        <table>
            <tr>
                <th>Judul</th>
                <th>Deskripsi</th>
                <th>Prioritas</th>
                <th>Status</th>
                <th>Kategori</th>
                <th>Jatuh Tempo</th>
                <th>Aksi</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): 
                $due_date = new DateTime($row['due_date']);
                $today = new DateTime();
                $interval = $today->diff($due_date);
                $days_left = $interval->days;
                $is_past = $due_date < $today;

                $due_class = '';
                if ($row['status'] === 'completed') {
                    $due_class = 'status-completed';
                } elseif ($is_past) {
                    $due_class = 'due-danger';
                } elseif ($days_left <= 3) {
                    $due_class = 'due-danger';
                } elseif ($days_left <= 7) {
                    $due_class = 'due-warning';
                } else {
                    $due_class = 'due-safe';
                }
            ?>
            <tr class="<?= $due_class ?>">
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= ucfirst($row['priority']) ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td>
                    <?php
                    if (!empty($row['category_id'])) {
                        $category_id = $row['category_id'];
                        $category_result = $conn->query("SELECT name FROM categories WHERE id = $category_id AND (user_id = $user_id OR role = 'admin')");
                        if ($category_result && $category_result->num_rows > 0) {
                            $category = $category_result->fetch_assoc();
                            echo htmlspecialchars($category['name']);
                        } else {
                            echo "Tidak ada kategori";
                        }
                    } else {
                        echo "Tidak ada kategori";
                    }
                    ?>
                </td>
                <td><?= htmlspecialchars($row['due_date']) ?></td>
                <td class="actions">
                    <a href="edit-task.php?id=<?= $row['id'] ?>" class="edit" title="Edit">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <form action="delete.php" method="POST" style="display:inline;">
                        <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="delete" title="Hapus" onclick="return confirm('Hapus tugas ini?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <a href="update-status.php?id=<?= $row['id'] ?>" class="done" title="Ubah Status">
                         <i class="bi bi-check2-square"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>

<?php $conn->close(); ?>

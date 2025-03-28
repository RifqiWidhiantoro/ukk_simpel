<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login-page.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user'; // Default to 'user' if role is not set

$conn = new mysqli("localhost", "root", "", "todo_list");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Tambah Kategori
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        
        // Check if category name already exists for the user or admin
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND (user_id = ? OR role = 'admin')");
        $stmt->bind_param("si", $name, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Nama kategori sudah ada!'); window.history.back();</script>";
            exit();
        }
        $stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO categories (name, user_id, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $name, $user_id, $user_role);
        $stmt->execute();
        $stmt->close();
    } 
    // Edit Kategori
    elseif (isset($_POST['edit_category'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        
        // Check if category name already exists for the user or admin
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ? AND (user_id = ? OR role = 'admin')");
        $stmt->bind_param("sii", $name, $id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Nama kategori sudah ada!'); window.history.back();</script>";
            exit();
        }
        $stmt->close();
        
        // Hanya pemilik kategori atau admin yang bisa edit
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ? AND (user_id = ? OR role = 'admin')");
        $stmt->bind_param("sii", $name, $id, $user_id);
        $stmt->execute();
        $stmt->close();
    } 
    // Hapus Kategori
    elseif (isset($_POST['delete_category'])) {
        $id = intval($_POST['id']);
        
        // Hanya pemilik kategori atau admin yang bisa hapus
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND (user_id = ? OR role = 'admin')");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Menampilkan Kategori
// Admin melihat semua kategori, User melihat kategori miliknya dan kategori umum (buatan admin)
if ($user_role === 'admin') {
    $categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
} else {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE user_id = ? OR role = 'admin' ORDER BY name ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $categories = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - To-Do List</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            padding: 20px;
        }
        .container {
            max-width: 700px;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-group button {
            padding: 10px;
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-group button:hover {
            background: #3B82F6;
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
        .actions button {
            margin: 0 5px;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        .edit { background: #ffc107; }
        .delete { background: #dc3545; color: white; }
        .back { background: linear-gradient(135deg, #1E3A8A, #3B82F6); color: white; padding: 10px; border-radius: 5px; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Categories</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <button type="submit" name="add_category">Add Category</button>
            </div>
        </form>

        <h3>Existing Categories</h3>
        <table>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $categories->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td class="actions">
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
                        <button type="submit" name="edit_category" class="edit">Edit</button>
                    </form>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" name="delete_category" class="delete" onclick="return confirm('Hapus kategori ini?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <a href="dashboard.php" class="back">Back to Dashboard</a>
    </div>
</body>
</html>

<?php $conn->close(); ?>

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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $current_status = $_POST['current_status'];
    $new_status = $current_status === 'pending' ? 'progress' : 'completed';

    $update_sql = "UPDATE tasks SET status = '$new_status' WHERE id = $task_id AND user_id = $user_id";
    if ($conn->query($update_sql) === TRUE) {
        header("Location: details.php?id=$task_id");
        exit();
    } else {
        echo "Error updating status: " . $conn->error;
    }
}

// Fetch task details
$sql = "SELECT * FROM tasks WHERE id = $task_id AND user_id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "Tugas tidak ditemukan.";
    exit();
}

$task = $result->fetch_assoc();

// Fetch category name based on category_id
$category_name = "Tidak ada kategori"; // Default value if no category is found
if (!empty($task['category_id'])) {
    $category_id = intval($task['category_id']);
    $category_result = $conn->query("SELECT name FROM categories WHERE id = $category_id");
    if ($category_result->num_rows > 0) {
        $category_row = $category_result->fetch_assoc();
        $category_name = htmlspecialchars($category_row['name']);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tugas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background: linear-gradient(135deg, #1E3A8A, #3B82F6);
        animation: fadeIn 1s ease-in-out;
    }
    .container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 500px;
    }
    h2 {
        color: #1E3A8A;
        text-align: center;
        margin-bottom: 15px;
    }
    p {
        color: #333;
    }
    .highlight {
        background: #FFF3CD;
        border-left: 5px solid #ff7f50;
        padding: 10px;
        margin-bottom: 10px;
        font-style: italic;
        color: #856404;
    }
    .btn-container {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }
    .btn {
        padding: 10px 15px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        transition: 0.3s;
    }
    .btn-delete {
        background: #DC2626;
        color: white;
    }
    .btn-delete:hover {
        background: #B91C1C;
    }
    .btn-edit {
        background: #F59E0B;
        color: white;
    }
    .btn-edit:hover {
        background: #D97706;
    }
    .btn-complete {
        background: #10B981;
        color: white;
    }
    .btn-complete:hover {
        background: #059669;
    }
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @media (max-width: 600px) {
        .container {
            padding: 20px;
            max-width: 90%;
        }
    }
</style>

</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen p-4" style="background: linear-gradient(135deg, #1E3A8A, #3B82F6);">
    <div class="bg-white max-w-lg w-full p-6 rounded-2xl shadow-lg">
        <!-- Header -->
        <div class="flex items-center mb-4">
            <a href="dashboard.php" class="text-gray-600 text-2xl mr-3"><i class="bi bi-arrow-left"></i></a>
            <h2 class="text-xl font-semibold flex-1">Detail Tugas</h2>
        </div>

        <!-- Task Data for JS -->
        <div id="task-data"
             data-title="<?= htmlspecialchars($task['title']) ?>"
             data-description="<?= htmlspecialchars($task['description']) ?>"
             data-priority="<?= htmlspecialchars($task['priority']) ?>"
             data-category="<?= htmlspecialchars($category_name) ?>"
             data-due="<?= htmlspecialchars($task['due_date']) ?>"
             data-status="<?= htmlspecialchars($task['status']) ?>">
        </div>

        <!-- Task Title -->
        <div class="mt-4 border-l-4 border-yellow-400 pl-3">
            <span class="text-yellow-500 text-lg"></span>
            <h3 class="text-lg font-semibold"><?= htmlspecialchars($task['title']) ?></h3>
        </div>

        <!-- Task Description -->
        <p class="text-gray-600 mb-4">
            <br>
            <?= htmlspecialchars($task['description']) ?>
        </p>

        <!-- Highlighted Quote (Optional) -->
        <?php if (!empty($task['highlight'])): ?>
        <blockquote class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-3 italic mb-4">
            <?= htmlspecialchars($task['highlight']) ?>
        </blockquote>
        <?php endif; ?>

        <!-- Task Details -->
        <p class="text-gray-600 mb-4">
            <strong>Prioritas:</strong> <?= ucfirst($task['priority']) ?><br>
            <strong>Kategori:</strong> <?= htmlspecialchars($category_name) ?><br>
            <strong>Jatuh Tempo:</strong> <?= htmlspecialchars($task['due_date']) ?><br>
            <strong>Status:</strong> <?= ucfirst($task['status']) ?>
        </p>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
            <!-- Delete & Edit -->
            <div class="flex gap-4">
                <form action="delete.php" method="POST">
                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                    <button type="submit" class="text-red-500 text-xl hover:opacity-75" onclick="return confirm('Hapus tugas ini?')">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                <a href="edit-task.php?id=<?= $task['id'] ?>" class="text-yellow-500 text-xl hover:opacity-75">
                    <i class="bi bi-pencil"></i>
                </a>
                <button onclick="printTaskDetails()" class="text-blue-500 text-xl hover:opacity-75">
                    <i class="bi bi-printer"></i>
                </button>
            </div>

            <!-- Complete Button -->
            <?php if ($task['status'] !== 'completed'): ?>
            <form action="" method="POST">
                <input type="hidden" name="current_status" value="<?= $task['status'] ?>">
                <button type="submit" name="update_status" class="bg-green-500 text-white px-4 py-2 rounded-lg flex items-center hover:bg-green-600">
                    <i class="bi bi-check-lg mr-2"></i>
                    <?= $task['status'] === 'pending' ? 'Progress' : 'Completed' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Print Script -->
    <script>
    function printTaskDetails() {
        const taskData = document.getElementById('task-data');
        const title = taskData.dataset.title;
        const description = taskData.dataset.description;
        const priority = taskData.dataset.priority;
        const category = taskData.dataset.category;
        const due = taskData.dataset.due;
        const status = taskData.dataset.status;

        const printContent = `
            <html>
            <head>
                <title>Detail Tugas</title>
                <style>
                    body {
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        margin: 40px;
                        color: #222;
                        background: #fff;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    .header h2 {
                        color: #1E3A8A;
                        margin: 0;
                    }
                    .task-details {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 10px;
                    }
                    .task-details th,
                    .task-details td {
                        text-align: left;
                        padding: 10px 15px;
                        border-bottom: 1px solid #ddd;
                    }
                    .task-details th {
                        background-color: #f0f4ff;
                        color: #1E3A8A;
                        width: 200px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 40px;
                        font-size: 0.9rem;
                        color: #888;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Detail Tugas</h2>
                </div>
                <table class="task-details">
                    <tr><th>Judul</th><td>${title}</td></tr>
                    <tr><th>Deskripsi</th><td>${description}</td></tr>
                    <tr><th>Prioritas</th><td>${priority.charAt(0).toUpperCase() + priority.slice(1)}</td></tr>
                    <tr><th>Kategori</th><td>${category}</td></tr>
                    <tr><th>Jatuh Tempo</th><td>${due}</td></tr>
                    <tr><th>Status</th><td>${status.charAt(0).toUpperCase() + status.slice(1)}</td></tr>
                </table>
                <div class="footer">
                    Dicetak otomatis oleh KeepItDone &copy; 2025
                </div>
            </body>
            </html>
        `;

        const newWindow = window.open('', '_blank');
        newWindow.document.write(printContent);
        newWindow.document.close();
        newWindow.focus();
        newWindow.print();
    }
    </script>
</body>

<?php $conn->close(); ?>

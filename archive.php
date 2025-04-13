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

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? $conn->real_escape_string($_GET['filter_status']) : 'all';
$sort_by = isset($_GET['sort_by']) ? $conn->real_escape_string($_GET['sort_by']) : 'title';

$query = "SELECT tasks.*, categories.name AS category_name 
          FROM tasks 
          LEFT JOIN categories ON tasks.category_id = categories.id 
          WHERE tasks.user_id = $user_id";

if (!empty($search)) {
    $query .= " AND (tasks.title LIKE '%$search%' OR tasks.description LIKE '%$search%')";
}

if ($filter_status !== 'all') {
    $query .= " AND tasks.status = '$filter_status'";
}

switch ($sort_by) {
    case 'priority':
        $query .= " ORDER BY FIELD(tasks.priority, 'high', 'medium', 'low')";
        break;
    case 'category':
        $query .= " ORDER BY categories.name ASC";
        break;
    case 'due_date':
        $query .= " ORDER BY tasks.due_date ASC";
        break;
    case 'status':
        $query .= " ORDER BY FIELD(tasks.status, 'pending', 'progress', 'completed')";
        break;
    default:
        $query .= " ORDER BY tasks.title ASC";
        break;
}

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Archive</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #3B82F6;
            --dark: #1E293B;
            --light: #F8FAFC;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            color: var(--light);
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            animation: fadeIn 0.6s ease-in-out;
        }

        a.back-btn {
            display: inline-block;
            background-color: white;
            color: #1E3A8A;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            margin-bottom: 20px;
            transition: 0.3s ease;
        }

        a.back-btn:hover {
            background-color: #e2e8f0;
        }

        h2 {
            text-align: center;
            color: var(--light);
            margin-bottom: 20px;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: space-between;
            background-color: rgba(30, 58, 138, 0.8);
            padding: 15px;
            border-radius: 10px;
            animation: slideDown 0.5s ease-in-out;
        }

        .filters input, .filters select, .filters button {
            padding: 10px;
            border: none;
            border-radius: 6px;
            width: 100%;
            max-width: 200px;
            font-size: 14px;
        }

        .filters button {
            background-color: var(--primary);
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        .task-card {
    background-color: #e2e8f0; /* dari rgba(15, 23, 42, 0.9) */
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    display: flex;
    flex-direction: column;
    gap: 10px;
    animation: fadeInUp 0.4s ease-in-out;
    color: #1e293b; /* agar teks tetap terlihat di background terang */
}


        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .task-title {
            font-size: 18px;
            font-weight: bold;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: capitalize;
            font-weight: bold;
            color: white;
        }

        /* Prioritas */
        .priority-high { background-color: #DC2626; }
        .priority-medium { background-color: #FBBF24; color: black; }
        .priority-low { background-color: #16A34A; }

        /* Status */
        .status-pending { background-color: #F87171; }
        .status-progress { background-color: #FACC15; color: black; }
        .status-completed { background-color: #4ADE80; color: black; }

        .task-footer {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            font-size: 14px;
        }

        .actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }

        .actions a {
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .actions a:hover {
            background-color: #2563EB;
        }

        @media (min-width: 768px) {
            .filters {
                flex-wrap: nowrap;
            }

            .task-footer {
                flex-wrap: nowrap;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn"></i> Kembali ke Dashboard</a>
        <h2>Task Archive</h2>
        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search tasks..." value="<?= htmlspecialchars($search) ?>">
            <select name="filter_status">
                <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="progress" <?= $filter_status === 'progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <select name="sort_by">
                <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>>Sort by Title</option>
                <option value="priority" <?= $sort_by === 'priority' ? 'selected' : '' ?>>Sort by Priority</option>
                <option value="category" <?= $sort_by === 'category' ? 'selected' : '' ?>>Sort by Category</option>
                <option value="due_date" <?= $sort_by === 'due_date' ? 'selected' : '' ?>>Sort by Due Date</option>
                <option value="status" <?= $sort_by === 'status' ? 'selected' : '' ?>>Sort by Status</option>
            </select>
            <button type="submit">Apply</button>
        </form>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($task = $result->fetch_assoc()): ?>
                <div class="task-card">
                    <div class="task-header">
                        <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                        <div class="badge priority-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></div>
                    </div>
                    <div class="task-footer">
                        <div><strong>Category:</strong> <?= htmlspecialchars($task['category_name'] ?? 'No Category') ?></div>
                        <div><strong>Due:</strong> <?= htmlspecialchars($task['due_date']) ?></div>
                        <div class="badge status-<?= $task['status'] ?>"><?= ucfirst($task['status']) ?></div>
                    </div>
                    <div class="actions">
                        <a href="details.php?id=<?= $task['id'] ?>"><i class="bi bi-eye"></i> View</a>
                        <a href="edit-task.php?id=<?= $task['id'] ?>"><i class="bi bi-pencil-square"></i> Edit</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; font-style: italic;">No tasks found.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?>

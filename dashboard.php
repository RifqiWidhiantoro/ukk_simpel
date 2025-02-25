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

// Dapatkan user_id
$user_id = $_SESSION['user_id'];

// Tanggal hari ini
$today = date('Y-m-d');

// Ambil parameter date dari URL, jika tidak ada pakai $today
$filter_date = isset($_GET['date']) ? $_GET['date'] : $today;

// Query menampilkan tasks pada $filter_date, diurutkan berdasarkan prioritas dan status
$sql = "
    SELECT * FROM tasks 
    WHERE user_id = $user_id
      AND due_date = '$filter_date'
    ORDER BY 
        CASE 
            WHEN priority = 'high' THEN 1
            WHEN priority = 'medium' THEN 2
            WHEN priority = 'low' THEN 3
            ELSE 4
        END, 
        status ASC
";
$result = $conn->query($sql);

// Hitung total tasks pada $filter_date
$count_sql = "SELECT COUNT(*) AS total_today 
              FROM tasks 
              WHERE user_id = $user_id
                AND due_date = '$filter_date'";
$count_res = $conn->query($count_sql);
$count_row = $count_res->fetch_assoc();
$tasks_today = $count_row['total_today'];

// Buat range 7 hari ke depan mulai dari $filter_date
$dates = [];
for ($i = 0; $i < 7; $i++) {
    $date_loop = date('Y-m-d', strtotime("$filter_date +$i day"));
    $dates[] = $date_loop;
}

// Prev: -7 hari dari $filter_date
$prev_date = date('Y-m-d', strtotime($filter_date . ' -7 day'));
// Next: +7 hari dari $filter_date
$next_date = date('Y-m-d', strtotime($filter_date . ' +7 day'));

// Ambil jumlah tugas untuk setiap tanggal dalam range
$task_counts = [];
foreach ($dates as $date) {
    $task_count_sql = "
        SELECT COUNT(*) AS count 
        FROM tasks 
        WHERE user_id = $user_id 
          AND due_date = '$date'
          AND status != 'completed'
    ";
    $task_count_res = $conn->query($task_count_sql);
    $task_count_row = $task_count_res->fetch_assoc();
    $task_counts[$date] = $task_count_row['count'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - To-Do List</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        /* Layout Umum */
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
        .dashboard-container {
            max-width: 480px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        header {
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 20px;
        }
        header h2 {
            font-size: 1.2rem;
            color: #333;
        }
        header .nav-links a {
            text-decoration: none;
            margin-left: 10px;
            color: #1E3A8A;
            font-weight: bold;
        }

        /* Info Hari Ini */
        .today-info {
            text-align: center;
            margin-bottom: 10px;
        }
        .today-info h3 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 5px;
        }
        .today-info .task-count {
            color: #ff7f50; /* oranye lembut */
            font-weight: bold;
        }

        /* Navigasi Tanggal */
        .date-nav {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-bottom: 20px;
        }
        .date-nav a {
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 8px;
            background: #f4f4f4;
            color: #333;
            font-size: 0.9rem;
            position: relative;
        }
        .date-nav a.active {
            background: #1E3A8A;
            color: white;
        }
        .date-nav .prev-date,
        .date-nav .next-date {
            font-weight: bold;
        }
        .date-nav .task-marker {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.7rem;
            color: white;
            background: red;
        }

        /* Daftar Tugas */
        .task-list {
            display: flex; 
            flex-direction: column; 
            gap: 10px;
        }
        .task-item {
            background: #fdfdfd;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .task-item .info {
            text-align: left;
            max-width: 70%;
        }
        .task-item .info .title {
            font-weight: bold;
            margin-bottom: 4px;
            color: #333;
        }
        .task-item .info .desc {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 4px;
        }
        .task-item .info .meta {
            font-size: 0.8rem;
            color: #999;
        }
        .task-item.completed .title,
        .task-item.completed .desc,
        .task-item.completed .meta {
            text-decoration: line-through;
        }
        .task-item .actions {
            display: flex; 
            gap: 8px; 
            font-size: 1.2rem;
        }
        .actions form {
            display: inline;
        }

        /* Warna Berdasarkan Due Date & Status */
        .due-danger { background-color: rgba(255, 0, 0, 0.1); }
        .due-warning { background-color: rgba(255, 165, 0, 0.1); }
        .due-safe { background-color: rgba(0, 123, 255, 0, 1); }
        .status-completed {
            background-color: rgba(40, 167, 69, 0, 1);
        }

        /* Tombol Tambah Tugas */
        .add-task {
            text-align: center;
            margin-top: 20px;
        }
        .add-task-button {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            color: white;
            font-weight: bold;
            display: inline-block;
        }
        .add-task-button:hover {
            opacity: 0.9;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .dashboard-container {
                padding: 10px; 
                max-width: 100%;
                border-radius: 10px;
            }
            .task-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .task-item .actions {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h2>Dashboard</h2>
            <div class="nav-links">
                <a href="profile.php">Profil</a>
                <a href="manage-categories.php">Kategori</a>
            </div>
        </header>

        <!-- Info Hari Ini -->
        <div class="today-info">
            <h3><?= date('l, d F Y', strtotime($filter_date)) ?></h3>
            <p><span class="task-count"><?= $tasks_today ?></span> Task Hari Ini</p>
        </div>

        <!-- Navigasi Tanggal (1 minggu) -->
        <div class="date-nav">
            <!-- Tombol Prev 7 Hari -->
            <a href="?date=<?= $prev_date ?>" class="prev-date">&laquo; Prev 7d</a>

            <!-- Tanggal Harian (opsional) -->
            <?php foreach ($dates as $d): 
                $active = ($d === $filter_date) ? 'active' : '';
                $task_marker = '';
                if ($task_counts[$d] > 0) {
                    $task_marker = '<div class="task-marker">' . $task_counts[$d] . '</div>';
                }
            ?>
            <a href="?date=<?= $d ?>" class="<?= $active ?>">
                <?= date('D, j', strtotime($d)) ?>
                <?= $task_marker ?>
            </a>
            <?php endforeach; ?>

            <!-- Tombol Next 7 Hari -->
            <a href="?date=<?= $next_date ?>" class="next-date">Next 7d &raquo;</a>
        </div>

        <!-- Daftar Tugas -->
        <div class="task-list">
            <?php while ($row = $result->fetch_assoc()):
                // Hitung due date
                $due_date = new DateTime($row['due_date']);
                $today_dt = new DateTime();
                $interval = $today_dt->diff($due_date);
                $days_left = $interval->days;
                $is_past = $due_date < $today_dt;

                // Tentukan class
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

                // Ambil nama kategori
                $cat_name = 'Tidak ada kategori';
                if (!empty($row['category_id'])) {
                    $category_id = $row['category_id'];
                    $category_result = $conn->query("
                        SELECT name FROM categories 
                        WHERE id = $category_id
                          AND (user_id = $user_id OR role = 'admin')
                    ");
                    if ($category_result && $category_result->num_rows > 0) {
                        $cat_fetch = $category_result->fetch_assoc();
                        $cat_name = htmlspecialchars($cat_fetch['name']);
                    }
                }
            ?>
            <div class="task-item <?= $due_class ?> <?= $row['status'] === 'completed' ? 'completed' : '' ?>">
                <div class="info">
                    <div class="title"><?= htmlspecialchars($row['title']) ?></div>
                    <div class="desc"><?= htmlspecialchars($row['description']) ?></div>
                    <div class="meta">
                        <?= ucfirst($row['priority']) ?> | 
                        <?= ucfirst($row['status']) ?> | 
                        Kategori: <?= $cat_name ?> | 
                        Due: <?= htmlspecialchars($row['due_date']) ?>
                    </div>
                </div>
                <div class="actions">
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
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Tombol Tambah Tugas -->
        <div class="add-task">
            <a href="task.php" class="add-task-button">Add New Task</a>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
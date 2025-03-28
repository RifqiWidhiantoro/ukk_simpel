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

// Get user_id
$user_id = $_SESSION['user_id'];

// Fetch user details for the profile photo
$user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_result->fetch_assoc();
$profile_photo = !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'pictures/default.png';

// Today's date
$today = date('Y-m-d');

// Date 3 days from now
$three_days_from_now = date('Y-m-d', strtotime('+3 days'));

// Query for tasks with a deadline 3 days from now
$reminder_sql = "
    SELECT title, due_date 
    FROM tasks 
    WHERE user_id = $user_id 
      AND due_date = '$three_days_from_now' 
      AND status != 'completed'
";
$reminder_result = $conn->query($reminder_sql);
$reminder_tasks = [];
if ($reminder_result->num_rows > 0) {
    while ($task = $reminder_result->fetch_assoc()) {
        $reminder_tasks[] = $task;
    }
}

// Get date parameter from URL or use today's date
$filter_date = isset($_GET['date']) ? $_GET['date'] : $today;

// Query to display tasks on the selected date, ordered by priority and status
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

// Count total tasks on the selected date
$count_sql = "
    SELECT COUNT(*) AS total_today 
    FROM tasks 
    WHERE user_id = $user_id
      AND due_date = '$filter_date'
";
$count_res = $conn->query($count_sql);
$count_row = $count_res->fetch_assoc();
$tasks_today = $count_row['total_today'];

// Generate a range of 5 days starting from the selected date
$dates = [];
for ($i = 0; $i < 5; $i++) {
    $date_loop = date('Y-m-d', strtotime("$filter_date +$i day"));
    $dates[] = $date_loop;
}

// Previous and next 5-day ranges
$prev_date = date('Y-m-d', strtotime($filter_date . ' -5 day'));
$next_date = date('Y-m-d', strtotime($filter_date . ' +5 day'));

// Count tasks for each date in the range
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
        header .logo-container {
            display: flex;
            align-items: center;
            gap: 10px; /* Add spacing between the logo and text */
        }
        header .logo-container .logo {
            width: 30px; /* Adjust the size of the logo */
            height: 30px;
            object-fit: contain; /* Ensure the logo fits within the dimensions */
        }
        header h2 {
            font-size: 1.2rem;
            color: #333;
            margin: 0;
        }
        header .nav-links {
            display: flex;
            align-items: center;
            gap: 15px; /* Add spacing between items */
        }
        header .nav-links a {
            text-decoration: none;
            color: #1E3A8A;
            font-weight: bold;
        }
        header .bell-icon {
            position: relative;
            cursor: pointer;
        }
        header .bell-icon .notification {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 15px;
            height: 15px;
            background: red;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .reminder-popup {
            display: none;
            position: absolute;
            top: 40px;
            left: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 10px;
            width: 300px;
            z-index: 1000;
        }
        .reminder-popup h4 {
            margin-bottom: 10px;
            font-size: 1rem;
            color: #333;
        }
        .reminder-popup ul {
            list-style: none;
            padding: 0;
        }
        .reminder-popup ul li {
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #555;
        }
        .reminder-popup ul li:last-child {
            margin-bottom: 0;
        }
        header .profile-photo img {
            width: 40px; /* Adjust the size of the profile photo */
            height: 40px;
            border-radius: 50%; /* Make the photo circular */
            object-fit: cover; /* Ensure the photo fits within the circle */
            border: 2px solid #1E3A8A; /* Optional: Add a border for better visibility */
            cursor: pointer; /* Indicate that the photo is clickable */
            transition: transform 0.2s ease;
        }
        header .profile-photo img:hover {
            transform: scale(1.1); /* Slight zoom effect on hover */
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer; /* Add pointer cursor */
            transition: transform 0.2s ease;
        }
        .task-item:hover {
            transform: scale(1.02); /* Slight zoom effect on hover */
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
        .due-warning { background-color: rgba(255, 165, 0, 0.2); }
        .due-safe { background-color: rgba(0, 123, 255, 0.1); }

        .status-completed {
            background-color: rgba(40, 167, 69, 0.4); /* Lebih tegas dari sebelumnya */
            color: #155724; /* Warna teks hijau lebih kontras */
        }

        .status-in-progress {
            background-color: rgba(255, 193, 7, 0.7) !important;
            color: #856404 !important;
        }

        .status-pending {
            background-color: rgba(220, 53, 69, 0.2); /* Warna merah tetap */
            color: #721c24; /* Warna teks lebih kontras */
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
            <div class="logo-container">
                <img src="pictures/logo.png" alt="Logo" class="logo">
                <h2>Dashboard</h2>
            </div>
            <div class="nav-links">
                <!-- Bell Icon -->
                <div class="bell-icon">
                    <i class="bi bi-bell"></i>
                    <?php if (count($reminder_tasks) > 0): ?>
                        <div class="notification"><?= count($reminder_tasks) ?></div>
                    <?php endif; ?>
                    <div class="reminder-popup">
                        <h4>Pengingat Tugas</h4>
                        <ul>
                            <?php if (count($reminder_tasks) > 0): ?>
                                <?php foreach ($reminder_tasks as $task): ?>
                                    <li><?= htmlspecialchars($task['title']) ?> (Due: <?= htmlspecialchars($task['due_date']) ?>)</li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>Tidak ada tugas yang perlu diingat.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Profile Photo -->
                <a href="profile.php" class="profile-photo">
                    <img src="<?= $profile_photo ?>" alt="Profile Photo">
                </a>

                <!-- Categories -->
                <a href="manage-categories.php">Kategori</a>
            </div>
        </header>

        <!-- Info Hari Ini -->
        <div class="today-info">
            <h3><?= date('l, d F Y', strtotime($filter_date)) ?></h3>
            <p><span class="task-count"><?= $tasks_today ?></span> Task Hari Ini</p>
        </div>

        <!-- Navigasi Tanggal (5 hari) -->
        <div class="date-nav">
            <!-- Tombol Prev 5 Hari -->
            <a href="?date=<?= $prev_date ?>" class="prev-date">&laquo; Prev 5d</a>

            <!-- Tanggal Harian -->
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

            <!-- Tombol Next 5 Hari -->
            <a href="?date=<?= $next_date ?>" class="next-date">Next 5d &raquo;</a>
        </div>

        <!-- Daftar Tugas -->
        <div class="task-list">
            <?php while ($row = $result->fetch_assoc()): 
                // Determine the color class based on the task status
                $status_class = '';
                if ($row['status'] === 'completed') {
                    $status_class = 'status-completed'; // Light green
                } elseif ($row['status'] === 'in-progress') {
                    $status_class = 'status-in-progress'; // Light yellow
                } elseif ($row['status'] === 'pending') {
                    $status_class = 'status-pending'; // Light red
                }
            ?>
            <div class="task-item <?= $status_class ?>" onclick="window.location.href='details.php?id=<?= $row['id'] ?>'">
                <div class="title"><?= htmlspecialchars($row['title']) ?></div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Tombol Tambah Tugas -->
        <div class="add-task">
            <a href="task.php" class="add-task-button">Add New Task</a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bellIcon = document.querySelector('.bell-icon');
            const reminderPopup = document.querySelector('.reminder-popup');

            // Toggle the visibility of the reminder popup
            bellIcon.addEventListener('click', function () {
                reminderPopup.style.display = reminderPopup.style.display === 'block' ? 'none' : 'block';
            });

            // Close the popup when clicking outside of it
            document.addEventListener('click', function (event) {
                if (!bellIcon.contains(event.target) && !reminderPopup.contains(event.target)) {
                    reminderPopup.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
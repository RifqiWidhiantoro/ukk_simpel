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
$user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .profile-container {
            max-width: 500px;
            background: white;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            color: #9b59b6;
            margin-bottom: 20px;
        }
        .profile-info {
            text-align: left;
            margin-bottom: 20px;
        }
        .profile-info p {
            margin: 10px 0;
            color: #333;
        }
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .actions a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
        }
        .edit-profile {
            background: #f39c12;
        }
        .logout {
            background: #e74c3c;
        }
        .logout:hover, .edit-profile:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Profil Pengguna</h2>
        <div class="profile-info">
            <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        </div>
        <div class="actions">
    <a href="edit-profile.php" class="edit-profile">
        <i class="bi bi-pencil-square"></i> Edit Profil
    </a>
    <a href="logout.php" class="logout">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

    </div>
</body>
</html>

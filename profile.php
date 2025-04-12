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
$conn->close();

// Jika tidak ada foto profil, gunakan default
$profile_photo = !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'pictures/default.png';
// Format tanggal bergabung jika ada
$joined_date = isset($user['created_at']) ? date('d F Y', strtotime($user['created_at'])) : '';
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
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .profile-container {
            max-width: 500px;
            background: white;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            color: #3B82F6;
            margin-bottom: 20px;
        }
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 4px solid #3B82F6;
        }
        .profile-info {
            text-align: left;
            margin-bottom: 20px;
            font-size: 1rem;
            color: #333;
        }
        .profile-info p {
            margin: 8px 0;
        }
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .actions a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            transition: opacity 0.3s;
        }
        .actions a:hover {
            opacity: 0.9;
        }
        .edit-profile { background: #f39c12; }
        .logout { background: #e74c3c; }
        .back-dashboard { background:linear-gradient(135deg, #1E3A8A, #3B82F6); }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Profil Pengguna</h2>
        <img src="<?= $profile_photo ?>" alt="Profile Photo" class="profile-photo">
        <div class="profile-info">
            <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <?php if ($joined_date): ?>
            <p><strong>Bergabung sejak:</strong> <?= $joined_date ?></p>
            <?php endif; ?>
        </div>
        <div class="actions">
        <a href="dashboard.php" class="back-dashboard">
                <i class="bi bi-house"></i> Dashboard
            </a>
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

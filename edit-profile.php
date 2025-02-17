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

// Ambil data pengguna
$result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? $conn->real_escape_string($_POST['username']) : '';
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $conn->real_escape_string($_POST['password']) : '';

    if (!empty($username) && !empty($email)) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET username='$username', email='$email', password='$hashed_password' WHERE id=$user_id");
        } else {
            $conn->query("UPDATE users SET username='$username', email='$email' WHERE id=$user_id");
        }

        if ($conn->affected_rows > 0) {
            $message = "Profil berhasil diperbarui.";
            echo "<script>
                    alert('Profil berhasil diperbarui.');
                    location.reload();
                  </script>";
            exit();
        } else {
            $message = "Tidak ada perubahan yang dilakukan.";
        }
    } else {
        $message = "Username dan email harus diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - To-Do List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .profile-container {
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
        input[type="email"], 
        input[type="password"],
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
    <div class="profile-container">
        <h2>Edit Profil</h2>
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($user['username']) ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <input type="password" name="password" placeholder="Kata Sandi Baru (Opsional)">
            <button type="submit">Simpan Perubahan</button>
        </form>
        <a class="back-link" href="profile.php"><i class="bi bi-arrow-left"></i> Kembali ke Profil</a>
    </div>
</body>
</html>

<?php $conn->close(); ?>

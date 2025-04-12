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
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? $conn->real_escape_string($_POST['username']) : '';
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $conn->real_escape_string($_POST['password']) : '';
    $profile_photo = $user['profile_photo'];

    // Handle file upload dengan validasi
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $max_size = 3 * 1024 * 1024; // 3 MB
        $file_name = $_FILES['profile_photo']['name'];
        $file_size = $_FILES['profile_photo']['size'];
        $file_tmp  = $_FILES['profile_photo']['tmp_name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_extensions)) {
            $message = "Hanya file JPG, JPEG, atau PNG yang diperbolehkan.";
        } elseif ($file_size > $max_size) {
            $message = "Ukuran file maksimal 3 MB.";
        } else {
            $upload_dir = 'pictures/';
            // Buat nama file baru menggunakan timestamp
            $new_file_name = $upload_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", $file_name);
            if (move_uploaded_file($file_tmp, $new_file_name)) {
                // Hapus foto lama jika bukan default dan file ada
                if ($user['profile_photo'] != 'pictures/default.png' && file_exists($user['profile_photo'])) {
                    unlink($user['profile_photo']);
                }
                $profile_photo = $new_file_name;
            } else {
                $message = "Gagal mengupload file.";
            }
        }
    }

    if (!empty($username) && !empty($email)) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_photo = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $email, $hashed_password, $profile_photo, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, profile_photo = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $profile_photo, $user_id);
        }
        $stmt->execute();
        
        if ($conn->affected_rows > 0) {
            $message = "Profil berhasil diperbarui.";
            echo "<script>
                    alert('Profil berhasil diperbarui.');
                    window.location.href = 'profile.php';
                  </script>";
            exit();
        } else {
            $message = "Tidak ada perubahan yang dilakukan.";
        }
        $stmt->close();
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
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
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
            text-align: center;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #3B82F6;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"],
        button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            color: white;
            cursor: pointer;
            border: none;
            font-weight: bold;
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
        .back-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            text-decoration: none;
            color: #6c757d;
        }
        .back-link:hover {
            color: #3B82F6;
        }
        .button-link {
        display: block;
        width: 100%;
        padding: 10px;
        background: linear-gradient(135deg, #3B82F6, #1E3A8A);
        color: white;
        text-align: center;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
        font-weight: bold;
        line-height: normal;
        border: none;
        margin-top: 10px;
        box-sizing: border-box;
        cursor: pointer;
        font-family: Arial, sans-serif;
        }

        .button-link:hover {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
        }

    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Edit Profil</h2>
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($user['username']) ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <input type="password" name="password" placeholder="Kata Sandi Baru (Opsional)">
            <input type="file" name="profile_photo" accept="image/jpeg, image/jpg, image/png">
            <button type="submit">Simpan Perubahan</button>
        </form>
        <a class="button-link" href="profile.php"> Profile</a>
        </div>
</body>
</html>

<?php $conn->close(); ?>

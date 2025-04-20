<?php
session_start();
$conn = new mysqli("localhost", "root", "", "todo_list");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["role"] = $role;
            $_SESSION["username"] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Password salah!'); window.location.href = 'login-page.php';</script>";
        }
    } else {
        echo "<script>alert('Username tidak ditemukan!'); window.location.href = 'login-page.php';</script>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - To-Do List</title>
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
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease-in-out;
        }

        .logo {
            width: 100px;
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #1E3A8A;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #3B82F6;
            border-radius: 5px;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color: #1E3A8A;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #1E3A8A;
        }

        .register-text {
            margin-top: 15px;
            font-size: 14px;
            color: #1E3A8A;
        }

        .register-link {
            color: #1E3A8A;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        .register-link:hover {
            color: #0F172A;
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
            .login-container {
                padding: 20px;
                max-width: 90%;
            }

            .input-group input {
                padding: 10px;
            }

            .btn {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="pictures/logo.png" alt="Logo" class="logo">
        <h2 style="color: #1E3A8A;">Login</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <p class="register-text">Belum punya akun? <a href="register-page.php" class="register-link">Daftar</a></p>
    </div>
</body>
</html>

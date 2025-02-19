<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - To-Do List</title>
    <!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - To-Do List</title>
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
        .register-container {
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
        .register-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border-color 0.3s;
        }
        .input-group input:focus {
            border-color: #3B82F6;
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
        .login-link {
            margin-top: 15px;
            display: block;
            color: #1E3A8A;
            text-decoration: none;
            transition: color 0.3s;
        }
        .login-link:hover {
            color: #3B82F6;
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
            .register-container {
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
    <div class="register-container">
        <img src="logo.png" alt="Logo" class="logo">
        <h2>Registrasi</h2>
        <form action="register-page.php" method="POST" onsubmit="return validateForm()">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Masukkan email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn">Daftar</button>
        </form>
        <a href="login-page.php" class="login-link">Sudah punya akun? Login</a>
    </div>
</body>
</html>
<script>
    function validateForm() {
        const password = document.getElementById("password").value;
        const confirm_password = document.getElementById("confirm_password").value;
        if (password !== confirm_password) {
            alert("Password dan Konfirmasi Password tidak cocok!");
            return false;
        }
        if (password.length < 8) {
            alert("Password harus minimal 8 karakter!");
            return false;
        }
        return true;
    }
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Format email tidak valid!'); window.location.href = 'register-page.php';</script>";
        exit();
    }
    if ($password !== $confirm_password) {
        echo "<script>alert('Password dan Konfirmasi Password tidak cocok!'); window.location.href = 'register-page.php';</script>";
        exit();
    }
    if (strlen($password) < 8) {
        echo "<script>alert('Password harus minimal 8 karakter!'); window.location.href = 'register-page.php';</script>";
        exit();
    }

    $conn = new mysqli("localhost", "root", "", "todo_list");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Username atau Email sudah terdaftar!'); window.location.href = 'register-page.php';</script>";
        exit();
    }
    $stmt->close();
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location.href = 'login-page.php';</script>";
    } else {
        echo "<script>alert('Registrasi gagal, silakan coba lagi!'); window.location.href = 'register-page.php';</script>";
    }
    
    $stmt->close();
    $conn->close();
}
?>

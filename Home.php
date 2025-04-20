<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "todo_list");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['anonymous'])) {
    $username = "Anonymous_" . uniqid();
    $email = '';
    $password = '';
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
    $stmt->bind_param("sss", $username, $email, $password);
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'user';
        $_SESSION['is_anonymous'] = true;
        header("Location: dashboard.php");
        exit();
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
    <title>Welcome - KeepItDone</title>
    <style>
        :root {
            --blue-dark: #1E3A8A;
            --blue-light: #3B82F6;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, var(--blue-dark), var(--blue-light));
        }

        .home-container {
            text-align: center;
            background-color: var(--white);
            color: var(--blue-dark);
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            max-width: 420px;
            width: 100%;
            animation: fadeIn 0.6s ease;
        }

        .home-container img {
            width: 80px;
            height: auto;
            margin-bottom: 20px;
        }

        .home-container h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .home-container p {
            font-size: 15px;
            margin-bottom: 25px;
            color: #334155;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px 0;
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--blue-dark), var(--blue-light));
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: var(--blue-dark);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="home-container">
        <img src="pictures/logo.png" alt="KeepItDone Logo">
        <h1>Welcome to KeepItDone</h1>
        <p>Organize your tasks effortlessly. Stay productive, one task at a time.</p>
        <a href="login-page.php" class="btn">Login</a>
        <a href="register-page.php" class="btn">Register</a>
        <form method="POST">
            <button type="submit" name="anonymous" class="btn">Try Without Account</button>
        </form>
    </div>
</body>
</html>
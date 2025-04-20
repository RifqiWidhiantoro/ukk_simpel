<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "todo_list";

// Buat koneksi
$conn = new mysqli($servername, $username, $password);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Buat database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database berhasil dibuat atau sudah ada.<br>";
} else {
    echo "Error membuat database: " . $conn->error . "<br>";
}

// Pilih database
$conn->select_db($dbname);

// Buat tabel Users
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT 'pictures/default.png',
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
)";
$conn->query($sql);

// Buat tabel Categories dengan user_id dan role
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    user_id INT NULL,
    role ENUM('admin', 'user', 'anonymous') NOT NULL DEFAULT 'user',
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Buat tabel Tasks
$sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('high', 'medium', 'low') NOT NULL DEFAULT 'medium',
    due_date DATE,
    status ENUM('pending', 'progress', 'completed') NOT NULL DEFAULT 'pending',
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";
$conn->query($sql);

// Buat tabel Task Assignments
$sql = "CREATE TABLE IF NOT EXISTS task_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    assigned_user_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Insert Default Data
$sql = "INSERT INTO users (username, email, password, role) 
        VALUES ('admin', 'admin@example.com', 'hashedpassword', 'admin')
        ON DUPLICATE KEY UPDATE email=email";
$conn->query($sql);

$sql = "INSERT INTO categories (name, user_id, role) 
        VALUES 
            ('Work', NULL, 'admin'), 
            ('Personal', NULL, 'admin'), 
            ('Others', NULL, 'admin') 
        ON DUPLICATE KEY UPDATE name=name";
$conn->query($sql);

echo "Semua tabel berhasil dibuat atau sudah ada.";

$conn->close();
?>

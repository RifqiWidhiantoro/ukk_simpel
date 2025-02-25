<?php
session_start();
$conn = new mysqli("localhost", "root", "", "todo_list");
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['order'])) {
    foreach ($data['order'] as $position => $task_id) {
        $stmt = $conn->prepare("UPDATE tasks SET position = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $position, $task_id, $_SESSION['user_id']);
        $stmt->execute();
    }
}
?>

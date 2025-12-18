<?php
// 1. Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? '';
    $identifier = trim($_POST['identifier'] ?? ''); // This is the Roll Number or Email

    if ($role === "student") {
        $name = trim($_POST['name'] ?? '');

        if (empty($name) || empty($identifier)) {
            $_SESSION['error'] = "Both Name and Roll Number are required.";
            header("Location: login.html");
            exit;
        }

        // --- MATCHED TO YOUR DB: student_name and student_roll_no ---
        $stmt = $conn->prepare("SELECT student_id, student_name FROM students WHERE student_name = ? AND student_roll_no = ?");
        
        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $name, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // SUCCESS
            $_SESSION['user_id'] = $user['student_id']; // Matches student_id
            $_SESSION['user_name'] = $user['student_name']; // Matches student_name
            $_SESSION['user_role'] = 'student';
            header("Location: student_dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Student not found. Please check your credentials.";
            header("Location: login.html");
            exit;
        }

    } elseif ($role === "admin") {
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $_SESSION['error'] = "Email and Password are required.";
            header("Location: login.html");
            exit;
        }

        // --- MATCHED TO YOUR DB: id, name, email, password ---
        $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE email = ?");
        
        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Simple string comparison for password (as requested)
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = 'admin';
                header("Location: admin_portal.php");
                exit;
            } else {
                $_SESSION['error'] = "Incorrect password.";
                header("Location: login.html");
                exit;
            }
        } else {
            $_SESSION['error'] = "Admin email not found.";
            header("Location: login.html");
            exit;
        }
    }

    if (isset($stmt)) { $stmt->close(); }
    $conn->close();
}
?>
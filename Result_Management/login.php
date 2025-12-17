<?php
// 1. Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $identifier = trim($_POST['identifier']); // Roll No for Student, Email for Admin

    if ($role === "student") {
        $name = trim($_POST['name']);

        if (empty($name) || empty($identifier)) {
            die("Error: Both Name and Roll Number are required.");
        }

        // Student Check: Comparing Name and Roll Number
        $stmt = $conn->prepare("SELECT student_id, student_name FROM students WHERE student_name = ? AND student_roll_no = ?");
        
        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param("ss", $name, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Success
            $_SESSION['user_id'] = $user['student_id'];
            $_SESSION['user_name'] = $user['student_name'];
            $_SESSION['user_role'] = 'student';
            header("Location: student_dashboard.php");
            exit;
        } else {
            die("Error: Student not found. Please check your Name and Roll Number.");
        }

    } elseif ($role === "admin") {
        $password = $_POST['password'];

        if (empty($identifier) || empty($password)) {
            die("Error: Email and Password are required.");
        }

        // Admin Check
        $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE email = ?");
        
        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = 'admin';
                header("Location: admin_dashboard.php");
                exit;
            } else {
                die("Error: Incorrect password.");
            }
        } else {
            die("Error: Admin email not found.");
        }
    }

    $stmt->close();
    $conn->close();
}
?>
<?php
session_start();
require "config.php";

// Auth check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit;
}

$user_name = $_SESSION['user_name'];
$roll_no_id = $_SESSION['roll_no_identifier'];

// Fetch all course data grouped by semester
$courses = [];
// Using TRIM to ensure codes like URCA-5101 match correctly
$course_q = "SELECT 
                semester_number, 
                TRIM(course_code) as course_code, 
                TRIM(subject_name) as subject_name 
             FROM semester_results 
             WHERE TRIM(student_roll_no) = TRIM(?) 
             ORDER BY semester_number ASC, course_code ASC";

$stmt = $conn->prepare($course_q);
$stmt->bind_param("s", $roll_no_id);
$stmt->execute();
$course_res = $stmt->get_result();

while ($row = $course_res->fetch_assoc()) {
    $courses[$row['semester_number']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Courses | <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Reuse your existing CSS variables and styles */
        :root { --navy: #002147; --gold: #C5A059; --white: #ffffff; --bg: #f4f7f9; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; }
        
        .sidebar { width: 260px; background: var(--navy); height: 100vh; color: var(--white); position: fixed; }
        .logo-section { padding: 40px 20px; text-align: center; border-bottom: 1px solid rgba(197, 160, 89, 0.2); }
        .logo-section h2 { font-family: 'Cinzel', serif; color: var(--gold); margin: 0; }
        
        .nav-links { padding: 20px 0; }
        .nav-item { padding: 15px 25px; display: block; color: #bdc3c7; text-decoration: none; border-left: 4px solid transparent; }
        .nav-item:hover, .nav-item.active { background: rgba(197, 160, 89, 0.1); color: var(--gold); border-left: 4px solid var(--gold); }
        
        .main { margin-left: 235px; padding: 40px; width: 100%; }
        .header { margin-bottom: 40px; }
        .header h1 { font-family: 'Cinzel', serif; color: var(--navy); margin: 0; }
        
        .sem-card { background: var(--white); border-radius: 12px; margin-bottom: 30px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .sem-header { background: var(--navy); color: var(--gold); padding: 15px 25px; font-family: 'Cinzel', serif; letter-spacing: 1px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 25px; background: #f8f9fa; color: var(--navy); font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #eee; }
        td { padding: 15px 25px; border-bottom: 1px solid #f1f1f1; font-size: 0.95rem; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo-section">
        <h2>TUB <span style="color:var(--white)">RMS</span></h2>
    </div>
    <nav class="nav-links">
        <a href="student_dashboard.php" class="nav-item"><i class="fas fa-file-alt"></i> &nbsp; My Transcript</a>
        <a href="courses.php" class="nav-item active"><i class="fas fa-book"></i> &nbsp; Courses</a>
        <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> &nbsp; Profile</a>
        <a href="logout.php" class="nav-item" style="color: #e74c3c;"><i class="fas fa-power-off"></i> &nbsp; Logout</a>
    </nav>
</aside>

<div class="main">
    <div class="header">
        <h1>Registered <span style="color:var(--gold)">Courses</span></h1>
        <p style="color: #7f8c8d; margin-top: 5px;">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?> 
            &nbsp; | &nbsp; <i class="fas fa-id-card"></i> <?php echo $roll_no_id; ?>
        </p>
    </div>

    <?php if (empty($courses)): ?>
        <div class="sem-card" style="padding: 20px; text-align: center;">No courses found.</div>
    <?php else: ?>
        <?php foreach ($courses as $semNum => $semRow): ?>
            <div class="sem-card">
                <div class="sem-header">SEMESTER <?php echo $semNum; ?></div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Course Code</th>
                            <th>Subject Title</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($semRow as $course): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['subject_name'] ?? 'Title not available'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>

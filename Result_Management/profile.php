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

// 1. Logic to determine Program Name based on Roll No string
$program_search = "";
if (strpos($roll_no_id, 'COMP') !== false) {
    $program_search = "BSCS"; // Or "BS Computer Science" depending on your table data
} elseif (strpos($roll_no_id, 'IT') !== false) {
    $program_search = "BSIT";
} elseif (strpos($roll_no_id, 'SE') !== false) {
    $program_search = "BSSE";
}

// 2. Fetch full student details and Program Name from the database
$student_data = null;
$query = "SELECT s.*, p.program_name 
          FROM students s
          LEFT JOIN programs p ON p.program_name LIKE ?
          WHERE TRIM(s.student_roll_no) = TRIM(?)";

$stmt = $conn->prepare($query);
$search_param = "%" . $program_search . "%";
$stmt->bind_param("ss", $search_param, $roll_no_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #002147; --gold: #C5A059; --white: #ffffff; --bg: #f4f7f9; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--navy); height: 100vh; color: var(--white); position: fixed; }
        .logo-section { padding: 40px 20px; text-align: center; border-bottom: 1px solid rgba(197, 160, 89, 0.2); }
        .logo-section h2 { font-family: 'Cinzel', serif; color: var(--gold); margin: 0; }
        .nav-links { padding: 20px 0; }
        .nav-item { padding: 15px 25px; display: block; color: #bdc3c7; text-decoration: none; border-left: 4px solid transparent; }
        .nav-item:hover, .nav-item.active { background: rgba(197, 160, 89, 0.1); color: var(--gold); border-left: 4px solid var(--gold); }
        .main { margin-left: 260px; padding: 40px; width: 100%; }
        .profile-card { background: var(--white); border-radius: 12px; max-width: 850px; margin: 0 auto; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .profile-header { background: var(--navy); color: var(--gold); padding: 40px; text-align: center; }
        .profile-avatar { width: 90px; height: 90px; background: var(--gold); color: var(--navy); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 15px; border: 3px solid var(--white); }
        .info-grid { padding: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .info-item { background: #fbfbfb; padding: 15px; border-radius: 8px; border-left: 3px solid var(--gold); }
        .info-label { font-size: 0.7rem; color: #7f8c8d; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 5px; }
        .info-value { font-size: 1.05rem; color: var(--navy); font-weight: 600; }
        .badge { background: var(--gold); color: var(--navy); padding: 2px 10px; border-radius: 4px; font-size: 0.8rem; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo-section">
        <h2>TUB <span style="color:var(--white)">RMS</span></h2>
    </div>
    <nav class="nav-links">
        <a href="student_dashboard.php" class="nav-item"><i class="fas fa-file-alt"></i> &nbsp; My Transcript</a>
        <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> &nbsp; Courses</a>
        <a href="profile.php" class="nav-item active"><i class="fas fa-user-circle"></i> &nbsp; Profile</a>
        <a href="logout.php" class="nav-item" style="color: #e74c3c;"><i class="fas fa-power-off"></i> &nbsp; Logout</a>
    </nav>
</aside>

<div class="main">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar"><i class="fas fa-user-graduate"></i></div>
            <h1 style="margin:0; font-family:'Cinzel', serif;"><?php echo htmlspecialchars($student_data['student_name'] ?? $user_name); ?></h1>
            <p style="color:rgba(255,255,255,0.8); margin-top:5px; letter-spacing:1px;">
                <?php echo htmlspecialchars($student_data['program_name'] ?? 'Degree Program'); ?>
            </p>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Student ID</div>
                <div class="info-value">#<?php echo htmlspecialchars($student_data['student_id'] ?? 'N/A'); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Roll Number</div>
                <div class="info-value"><?php echo htmlspecialchars($student_data['student_roll_no'] ?? $roll_no_id); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Degree Program</div>
                <div class="info-value"><?php echo htmlspecialchars($student_data['program_name'] ?? 'N/A'); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Admission Type</div>
                <div class="info-value" style="text-transform: capitalize;"><?php echo htmlspecialchars($student_data['admission_type'] ?? 'Regular'); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Batch ID</div>
                <div class="info-value"><?php echo htmlspecialchars($student_data['batch_id'] ?? 'N/A'); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">University</div>
                <div class="info-value">Thal University Bhakkar</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

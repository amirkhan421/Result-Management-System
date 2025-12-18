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

// 1. Fetch Batch ID
$stmt = $conn->prepare("SELECT batch_id FROM students WHERE student_roll_no = ?");
$stmt->bind_param("s", $roll_no_id);
$stmt->execute();
$batch_res = $stmt->get_result()->fetch_assoc();
$batch_id = $batch_res['batch_id'] ?? 0;

// 2. Fetch Curriculum
$curriculum = [];
$curr_q = "SELECT sem.semester_number, sm.subject_name, sm.subject_code, sm.credit_hours 
           FROM semesters sem
           JOIN semester_subjects ss ON sem.semester_id = ss.semester_id
           JOIN subject_master sm ON ss.subject_id = sm.subject_id
           WHERE sem.batch_id = ?
           ORDER BY sem.semester_number ASC, ss.id ASC";
$stmt = $conn->prepare($curr_q);
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$curr_res = $stmt->get_result();
while($row = $curr_res->fetch_assoc()){
    $curriculum[$row['semester_number']][] = $row;
}

// 3. Fetch Actual Results
$marks = [];
$marks_q = "SELECT * FROM semester_results WHERE student_roll_no = ? ORDER BY semester_number ASC, result_entry_id ASC";
$stmt = $conn->prepare($marks_q);
$stmt->bind_param("s", $roll_no_id);
$stmt->execute();
$marks_res = $stmt->get_result();
while($row = $marks_res->fetch_assoc()){
    $marks[$row['semester_number']][] = $row;
}

$total_cr = 0; $total_pts = 0; $total_obt = 0; $total_poss = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript | <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #002147;
            --gold: #C5A059;
            --white: #ffffff;
            --light-gold: rgba(197, 160, 89, 0.1);
            --bg: #f4f7f9;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            margin: 0;
            display: flex;
        }

        /* Sidebar Design */
        .sidebar {
            width: 260px;
            background: var(--navy);
            height: 100vh;
            color: var(--white);
            position: fixed;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .logo-section {
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(197, 160, 89, 0.2);
        }

        .logo-section h2 {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            margin: 0;
            color: var(--gold);
        }

        .nav-links {
            padding: 20px 0;
        }

        .nav-item {
            padding: 15px 25px;
            display: block;
            color: #bdc3c7;
            text-decoration: none;
            transition: 0.3s;
            border-left: 4px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: var(--light-gold);
            color: var(--gold);
            border-left: 4px solid var(--gold);
        }

        /* Main Content */
        .main {
            margin-left: 260px;
            padding: 40px;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-family: 'Cinzel', serif;
            color: var(--navy);
            margin: 0;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 8px;
            border-top: 4px solid var(--gold);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .stat-label {
            font-size: 0.75rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--navy);
            margin-top: 5px;
        }

        /* Semester Card */
        .sem-card {
            background: var(--white);
            border-radius: 12px;
            margin-bottom: 40px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .sem-header {
            background: var(--navy);
            color: var(--gold);
            padding: 15px 25px;
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 25px;
            background: #f8f9fa;
            color: var(--navy);
            font-size: 0.8rem;
            text-transform: uppercase;
            border-bottom: 2px solid #eee;
        }

        td {
            padding: 15px 25px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 0.95rem;
        }

        tr:hover {
            background-color: #fcf9f2;
        }

        .grade-badge {
            background: var(--navy);
            color: var(--gold);
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .gpa-row {
            background: #fdfaf3;
            font-weight: bold;
        }

        .logout-btn {
            background: transparent;
            color: var(--navy);
            border: 2px solid var(--navy);
            padding: 10px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: var(--navy);
            color: var(--gold);
        }

    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo-section">
        <h2>TUB <span style="color:var(--white)">RMS</span></h2>
        <p style="font-size: 0.7rem; color: var(--gold); margin-top: 5px; letter-spacing: 1px;">STUDENT PORTAL</p>
    </div>
    <nav class="nav-links">
        <a href="#" class="nav-item active"><i class="fas fa-file-alt"></i> &nbsp; My Transcript</a>
        <a href="#" class="nav-item"><i class="fas fa-book"></i> &nbsp; Courses</a>
        <a href="#" class="nav-item"><i class="fas fa-user-circle"></i> &nbsp; Profile</a>
        <a href="logout.php" class="nav-item" style="color: #e74c3c;"><i class="fas fa-power-off"></i> &nbsp; Logout</a>
    </nav>
</aside>

<div class="main">
    <div class="header">
        <div>
            <h1>Official <span style="color:var(--gold)">Transcript</span></h1>
            <p style="color: #7f8c8d; margin-top: 5px;">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?> 
                &nbsp; | &nbsp; <i class="fas fa-id-card"></i> <?php echo $roll_no_id; ?>
            </p>
        </div>
        <a href="logout.php" class="logout-btn">Sign Out</a>
    </div>

    <div class="stats-grid" id="stats-area"></div>

    <?php 
    foreach ($marks as $semNum => $semMarks): 
        if (!isset($curriculum[$semNum])) continue;
        $s_cr = 0; $s_pts = 0; $s_obt = 0;
    ?>
    <div class="sem-card">
        <div class="sem-header">SEMESTER <?php echo $semNum; ?></div>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Subject Title</th>
                    <th>Credits</th>
                    <th>Obtained</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($semMarks as $index => $mRow): 
                    $sub = $curriculum[$semNum][$index] ?? null;
                    if (!$sub) continue;

                    $total_cr += $sub['credit_hours'];
                    $total_pts += ($mRow['grade_point_total'] * $sub['credit_hours']);
                    $total_obt += $mRow['marks_obtained'];
                    $total_poss += 100;
                    
                    $s_cr += $sub['credit_hours'];
                    $s_pts += ($mRow['grade_point_total'] * $sub['credit_hours']);
                ?>
                <tr>
                    <td style="font-weight: 600; color: var(--navy);"><?php echo $sub['subject_code']; ?></td>
                    <td><?php echo $sub['subject_name']; ?></td>
                    <td><?php echo $sub['credit_hours']; ?></td>
                    <td><?php echo $mRow['marks_obtained']; ?></td>
                    <td><span class="grade-badge"><?php echo $mRow['grade_letter']; ?></span></td>
                </tr>
                <?php endforeach; ?>
                <tr class="gpa-row">
                    <td colspan="4" style="text-align: right; color: var(--navy);">SEMESTER GPA:</td>
                    <td style="color: var(--navy); font-size: 1.1rem;">
                        <?php echo ($s_cr > 0) ? number_format($s_pts/$s_cr, 2) : '0.00'; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <?php 
    $final_cgpa = ($total_cr > 0) ? number_format($total_pts / $total_cr, 2) : '0.00';
    $final_per = ($total_poss > 0) ? number_format(($total_obt / $total_poss) * 100, 1) : '0';
    ?>
</div>

<script>
    document.getElementById('stats-area').innerHTML = `
        <div class="stat-card">
            <div class="stat-label">Cumulative GPA</div>
            <div class="stat-value" style="color:var(--gold)">${'<?php echo $final_cgpa; ?>'}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Aggregate %</div>
            <div class="stat-value">${'<?php echo $final_per; ?>'}%</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Credits Earned</div>
            <div class="stat-value">${'<?php echo $total_cr; ?>'}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Marks Summary</div>
            <div class="stat-value" style="font-size:1.2rem; margin-top:12px;">
                ${'<?php echo $total_obt; ?>'} <span style="color:#bdc3c7; font-size:0.9rem;">/ ${'<?php echo $total_poss; ?>'}</span>
            </div>
        </div>
    `;
</script>

</body>
</html>
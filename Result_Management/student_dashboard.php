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

// 1. Student ka batch_id aur details nikaalna
$stmt = $conn->prepare("SELECT batch_id FROM students WHERE student_roll_no = ?");
$stmt->bind_param("s", $roll_no_id);
$stmt->execute();
$batch_res = $stmt->get_result()->fetch_assoc();
$batch_id = $batch_res['batch_id'] ?? 0;

// 2. Us Batch ka sara Curriculum (Subjects) nikaalna
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

// 3. Student ke actual Results (Marks) nikaalna
$marks = [];
$marks_q = "SELECT * FROM semester_results WHERE student_roll_no = ? ORDER BY semester_number ASC, result_entry_id ASC";
$stmt = $conn->prepare($marks_q);
$stmt->bind_param("s", $roll_no_id);
$stmt->execute();
$marks_res = $stmt->get_result();
while($row = $marks_res->fetch_assoc()){
    $marks[$row['semester_number']][] = $row;
}

// Global Stats
$total_cr = 0; $total_pts = 0; $total_obt = 0; $total_poss = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Transcript | RMS </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4361ee; --sidebar: #111827; --bg: #f8f9fd; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); margin: 0; display: flex; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar); height: 100vh; color: #fff; position: fixed; }
        .logo { padding: 30px; font-size: 1.5rem; font-weight: 800; color: var(--primary); border-bottom: 1px solid #1e293b; }
        
        /* Main Content */
        .main { margin-left: 260px; padding: 40px; width: 100%; box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid var(--primary); }
        .stat-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-top: 5px; }

        /* Semester Tables */
        .sem-card { background: #fff; border-radius: 12px; margin-bottom: 30px; overflow: hidden; box-shadow: 0 10px 15px rgba(0,0,0,0.05); }
        .sem-header { background: #f8fafc; padding: 15px 25px; font-weight: bold; border-bottom: 1px solid #e2e8f0; color: var(--primary); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 25px; background: #fafafa; color: #475569; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px 25px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .grade-badge { background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 6px; font-weight: bold; }
        .logout { background: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; float: right; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">RMS 
        <div style="color:white">Student Portal</div>
    </div>
        
    
    <div style="padding: 20px 30px; color: #94a3b8;"><i class="fas fa-home"></i> &nbsp; Dashboard</div>
</div>

<div class="main">
    <div class="header">
        <div>
            <h1 style="margin:0">Academic Transcript</h1>
            <p style="color: #64748b;">Student: <?php echo htmlspecialchars($user_name); ?> (<?php echo $roll_no_id; ?>)</p>
        </div>
        <a href="logout.php" class="logout">Logout</a>
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
                    <th>Subject</th>
                    <th>Credits</th>
                    <th>Obtained</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($semMarks as $index => $mRow): 
                    // Matching marks with curriculum based on order (Index)
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
                    <td style="font-weight: bold; color: var(--primary);"><?php echo $sub['subject_code']; ?></td>
                    <td><?php echo $sub['subject_name']; ?></td>
                    <td><?php echo $sub['credit_hours']; ?></td>
                    <td><?php echo $mRow['marks_obtained']; ?></td>
                    <td><span class="grade-badge"><?php echo $mRow['grade_letter']; ?></span></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background: #fcfcff; font-weight: bold;">
                    <td colspan="4" style="text-align: right;">Semester GPA:</td>
                    <td style="color: #10b981;"><?php echo ($s_cr > 0) ? number_format($s_pts/$s_cr, 2) : '0.00'; ?></td>
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
    // Updating Stats Cards dynamically via JavaScript to reflect calculated totals
    document.getElementById('stats-area').innerHTML = `
        <div class="stat-card"><div class="stat-label">CGPA</div><div class="stat-value">${'<?php echo $final_cgpa; ?>'}</div></div>
        <div class="stat-card"><div class="stat-label">Percentage</div><div class="stat-value">${'<?php echo $final_per; ?>'}%</div></div>
        <div class="stat-card"><div class="stat-label">Total Credits</div><div class="stat-value">${'<?php echo $total_cr; ?>'}</div></div>
        <div class="stat-card"><div class="stat-label">Total Marks</div><div class="stat-value">${'<?php echo $total_obt; ?>'} / ${'<?php echo $total_poss; ?>'}</div></div>
    `;
</script>

</body>
</html>
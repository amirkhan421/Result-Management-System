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

// 1. Fetch Semester Summaries
$summaries = [];
$sum_q = "SELECT * FROM semester_summary WHERE student_roll_no = ?";
$stmt = $conn->prepare($sum_q);
$stmt->bind_param("s", $roll_no_id);
$stmt->execute();
$sum_res = $stmt->get_result();
while ($row = $sum_res->fetch_assoc()) {
    $summaries[$row['semester_number']] = $row;
}

// 2. Fetch Detailed Results
$marks = [];
$marks_q = "SELECT 
                course_code, 
                subject_name, 
                marks_obtained, 
                grade_point_total, 
                grade_letter, 
                semester_number 
            FROM semester_results 
            WHERE student_roll_no = ? 
            ORDER BY semester_number ASC";

$stmt = $conn->prepare($marks_q);
$stmt->bind_param("s", $roll_no_id);
$stmt->execute();
$marks_res = $stmt->get_result();

while ($row = $marks_res->fetch_assoc()) {
    $marks[$row['semester_number']][] = $row;
}

// Global Variables
$total_cr = 0; 
$total_obt = 0; 
$total_poss = 0;

// Calculate CGPA
$total_semester_gpa_sum = 0;
$semester_count = count($summaries);

foreach ($summaries as $summary) {
    $total_semester_gpa_sum += (float)($summary['gpa'] ?? 0);
}

$final_cgpa = ($semester_count > 0) ? number_format($total_semester_gpa_sum / $semester_count, 2) : '0.00';

// Calculate totals for each course
foreach ($marks as $semesterRow) {
    foreach ($semesterRow as $mRow) {
        $earned_credits = (float)($mRow['grade_point_total'] ?? 0);
        $total_cr += $earned_credits;
        $total_obt += $mRow['marks_obtained'];
        $total_poss += 100; // Assuming each course is out of 100
    }
}

// Calculate Aggregate Percentage
$final_per = ($total_poss > 0) ? number_format(($total_obt / $total_poss) * 100, 2) : '0.00';
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

     /* ==================== DASHBOARD STYLES ==================== */
.dashboard {
    display: block;

    
}

        .print-transcript {
            display: none;
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
            margin-left: 230px;
            margin-right: -125px;
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

        /* ==================== PRINT TRANSCRIPT STYLES ==================== */
        .print-transcript-container {
            font-family: 'Times New Roman', serif;
            width: 21cm; /* A4 width */
            min-height: 29.7cm; /* A4 height */
            padding: 1.5cm;
            margin: 0 auto;
            background: white;
            box-sizing: border-box;
        }

        .transcript-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .university-id {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .university-name {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 1px;
        }

        .degree-program {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }

        .session-info {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .transcript-title {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
        }

        .student-info-print {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .print-table th {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .print-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
        }

        .semester-header-row {
            background-color: #e0e0e0 !important;
            font-weight: bold;
            text-align: left;
        }

        .semester-summary-row {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .summary-box {
            border: 1px solid #000;
            padding: 15px;
            margin-top: 30px;
            background-color: #f9f9f9;
            font-size: 13px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .signature-section {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            width: 220px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 40px 0 5px 0;
            height: 1px;
        }

        .signature-label {
            font-weight: bold;
            font-size: 13px;
        }

        .signature-sub {
            font-size: 11px;
            margin-top: 2px;
        }

        .footer-note {
            text-align: center;
            margin-top: 40px;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        /* ==================== PRINT MEDIA QUERIES ==================== */
        @media print {
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .dashboard {
                display: none !important;
            }
            
            .print-transcript {
                display: block !important;
            }
            
            .print-transcript-container {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            @page {
                margin: 1.5cm;
                size: A4 portrait;
            }
        }

        /* Print button styling */
        .print-btn {
            background: var(--navy);
            color: white;
            border: 2px solid var(--navy);
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-left: 10px;
        }

        .print-btn:hover {
            background: var(--gold);
            color: var(--navy);
        }
    </style>
</head>
<body>
    <!-- DASHBOARD VIEW -->
    <div class="dashboard">
        <aside class="sidebar">
            <div class="logo-section">
                <h2>TUB <span style="color:var(--white)">RMS</span></h2>
                <p style="font-size: 0.7rem; color: var(--gold); margin-top: 5px; letter-spacing: 1px;">STUDENT PORTAL</p>
            </div>
            <nav class="nav-links">
                <a href="#" class="nav-item active"><i class="fas fa-file-alt"></i> &nbsp; My Transcript</a>
                <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> &nbsp; Courses</a>
                <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> &nbsp; Profile</a>
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
                <div>
                    <button onclick="printTranscript()" class="print-btn">
                        <i class="fas fa-print"></i> Print Official Transcript</button>
                    <a href="logout.php" class="logout-btn">Sign Out</a>
                </div>
            </div>

            <div class="stats-grid" id="stats-area"></div>

            <?php foreach ($marks as $semNum => $semesterRow): 
                $semSummary = isset($summaries[$semNum]) ? $summaries[$semNum] : null;
                $semGPA = $semSummary ? $semSummary['gpa'] : "0.00";
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
                    <?php foreach ($semesterRow as $mRow): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mRow['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($mRow['subject_name'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($mRow['grade_point_total'], 2); ?></td>
                        <td><?php echo $mRow['marks_obtained']; ?></td>
                        <td><span class="grade-badge"><?php echo $mRow['grade_letter']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="gpa-row">
                        <td colspan="4" style="text-align:right;">Semester GPA</td>
                        <td><strong><?php echo number_format($semGPA, 2); ?></strong></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PRINT TRANSCRIPT VIEW (Hidden until print) -->
    <div class="print-transcript">
        <div class="print-transcript-container">
            <!-- Header Section -->
            <div class="transcript-header">
                <div class="university-id">IT-SP-261004</div>
                <h1 class="university-name">THAL UNIVERSITY BHAKKAR</h1>
                <div class="degree-program">BS COMPUTER SCIENCE</div>
                <div class="session-info">(Session 2022 - 2026)</div>
                <div class="transcript-title">Provisional Transcript (Subject to Final Evaluation)</div>
            </div>

            <!-- Student Information -->
            <div class="student-info-print">
                <div>
                    <strong>Name:</strong> <?php echo htmlspecialchars($user_name); ?><br>
                    <strong>Roll No:</strong> <?php echo $roll_no_id; ?>
                </div>
                <div>
                    <strong>Date:</strong> <?php echo date("d-M-Y"); ?>
                </div>
            </div>

            <!-- Transcript Table -->
            <table class="print-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title of Course</th>
                        <th>Credit Hours</th>
                        <th>Marks Obtained</th>
                        <th>Grade Points</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $semester_counter = 1;
                    foreach ($marks as $semNum => $semesterRow): 
                        $semSummary = isset($summaries[$semNum]) ? $summaries[$semNum] : null;
                        $semGPA = $semSummary ? $semSummary['gpa'] : "0.00";
                        $semCreditHours = 0;
                        
                        foreach ($semesterRow as $mRow) {
                            $semCreditHours += $mRow['grade_point_total'];
                        }
                    ?>
                    <!-- Semester Header -->
                    <tr class="semester-header-row">
                        <td colspan="6">Semester-<?php echo $semester_counter; $semester_counter++; ?></td>
                    </tr>
                    
                    <!-- Course Rows -->
                    <?php foreach ($semesterRow as $mRow): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mRow['course_code']); ?></td>
                        <td style="text-align: left;"><?php echo htmlspecialchars($mRow['subject_name']); ?></td>
                        <td><?php echo number_format($mRow['grade_point_total'], 2); ?></td>
                        <td><?php echo $mRow['marks_obtained']; ?></td>
                        <td><?php echo number_format($mRow['grade_point_total'], 2); ?></td>
                        <td><?php echo $mRow['grade_letter']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- Semester Summary -->
                    <tr class="semester-summary-row">
                        <td colspan="4" style="text-align: right;">Credit Hours:</td>
                        <td><?php echo number_format($semCreditHours, 0); ?></td>
                        <td>GPA: <?php echo number_format($semGPA, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Final Summary Box -->
            <div class="summary-box">
                <div class="summary-row">
                    <span><strong>CGPA (out of 4.00):</strong></span>
                    <span><?php echo $final_cgpa; ?></span>
                </div>
                <div class="summary-row">
                    <span><strong>Total Marks:</strong></span>
                    <span><?php echo $total_poss; ?></span>
                </div>
                <div class="summary-row">
                    <span><strong>Marks Obtained:</strong></span>
                    <span><?php echo $total_obt; ?></span>
                </div>
                <div class="summary-row">
                    <span><strong>Percentage (%):</strong></span>
                    <span><?php echo $final_per; ?>%</span>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Student Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Controller of Examinations</div>
                    <div class="signature-sub">Thal University Bhakkar</div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="footer-note">
                <p>Note: Errors and Omissions are excepted.</p>
                <p>Generated on: <?php echo date("d-M-Y H:i:s"); ?></p>
            </div>
        </div>
    </div>

    <script>
        // Fill stats area
        document.getElementById('stats-area').innerHTML = `
            <div class="stat-card">
                <div class="stat-label">Cumulative GPA</div>
                <div class="stat-value" style="color:var(--gold)"><?php echo $final_cgpa; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Aggregate %</div>
                <div class="stat-value"><?php echo $final_per; ?>%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Credits Earned</div>
                <div class="stat-value"><?php echo number_format($total_cr, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Marks Summary</div>
                <div class="stat-value" style="font-size:1.2rem; margin-top:12px;">
                    <?php echo $total_obt; ?> <span style="color:#bdc3c7; font-size:0.9rem;">/ <?php echo $total_poss; ?></span>
                </div>
            </div>
        `;

        // Print function
        function printTranscript() {
            // Switch to print view
            document.querySelector('.dashboard').classList.add('no-print');
            document.querySelector('.print-transcript').style.display = 'block';
            
            // Trigger print
            setTimeout(() => {
                window.print();
            }, 100);
            
            // Switch back after printing
            setTimeout(() => {
                document.querySelector('.dashboard').classList.remove('no-print');
                document.querySelector('.print-transcript').style.display = 'none';
            }, 1000);
        }
    </script>
</body>
</html>

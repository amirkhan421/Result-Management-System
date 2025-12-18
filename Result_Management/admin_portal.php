<?php
// 1. DATABASE CONNECTION
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', ''); 
define('DB_NAME', 'result_management');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8");

// 2. AJAX API LOGIC
$action = $_GET['action'] ?? '';

if ($action === 'load') {
    header('Content-Type: application/json');
    $semester = filter_input(INPUT_GET, 'semester', FILTER_VALIDATE_INT);
    $sql = "SELECT ss.*, sm.student_name FROM semester_summary ss 
            JOIN students sm ON ss.student_roll_no = sm.student_roll_no 
            WHERE ss.semester_number = ? ORDER BY ss.student_roll_no ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $semester);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'delete') {
    header('Content-Type: application/json');
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM semester_summary WHERE summary_id = ?");
    $stmt->bind_param("i", $id);
    echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
    exit;
}

if ($action === 'add') {
    header('Content-Type: application/json');
    $roll = $_POST['roll'];
    $sem = $_POST['sem'];
    $marks = $_POST['marks'];
    $gpa = $_POST['gpa'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("INSERT INTO semester_summary (student_roll_no, semester_number, total_marks, gpa, result_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sidds", $roll, $sem, $marks, $gpa, $status);
    echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Portal | TUB University</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #002147; --gold: #C5A059; --white: #ffffff; --red: #e74c3c; }
        body { font-family: 'Inter', sans-serif; background: #f4f7f9; margin: 0; display: flex; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: var(--navy); height: 100vh; position: fixed; color: var(--white); display: flex; flex-direction: column; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(197, 160, 89, 0.2); }
        .sidebar-brand h2 { font-family: 'Cinzel', serif; margin: 0; font-size: 1.4rem; letter-spacing: 1px; }
        .nav-menu { padding: 20px 0; flex-grow: 1; }
        .nav-item { padding: 15px 25px; display: flex; align-items: center; color: #bdc3c7; text-decoration: none; transition: 0.3s; cursor: pointer; border-left: 4px solid transparent; }
        .nav-item:hover, .nav-item.active { background: rgba(197, 160, 89, 0.1); color: var(--gold); border-left-color: var(--gold); }
        .logout { color: var(--red) !important; border-top: 1px solid rgba(255,255,255,0.1); }

        /* Main Content */
        .content { margin-left: 280px; width: calc(100% - 280px); padding: 40px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-gold { background: var(--gold); color: var(--navy); border: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: 0.3s; }
        .btn-gold:hover { background: #b38f4d; transform: translateY(-2px); }

        /* Batch Selector & Table */
        .batch-card { background: var(--white); padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; border-top: 4px solid var(--gold); }
        .sem-grid { display: flex; gap: 8px; margin-top: 15px; }
        .sem-btn { flex: 1; padding: 10px; border: 1px solid #ddd; background: white; cursor: pointer; font-weight: 600; border-radius: 4px; }
        .sem-btn.active { background: var(--navy); color: var(--gold); border-color: var(--navy); }
        
        .table-container { background: var(--white); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; }
        th { background: var(--navy); color: var(--gold); padding: 15px; text-align: left; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .btn-del { color: var(--red); background: none; border: none; cursor: pointer; font-size: 1.1rem; }

        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 400px; position: relative; }
        .modal-content h3 { margin-top: 0; color: var(--navy); font-family: 'Cinzel', serif; }
        .modal-content input, .modal-content select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand"><h2>TUB <span style="color:var(--gold)">ADMIN</span></h2></div>
        <nav class="nav-menu">
            <a onclick="location.reload()" class="nav-item active"><i class="fas fa-th-list"></i> Batch Summary</a>
            <a onclick="openModal()" class="nav-item"><i class="fas fa-user-plus"></i> Add New Record</a>
            <a href="#" class="nav-item"><i class="fas fa-database"></i> Full Database</a>
            <a href="#" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        </nav>
        <a href="logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="content">
        <div class="header-bar">
            <h1 style="font-family:'Cinzel';">Batch <span style="color:var(--gold)">Management</span></h1>
            <button class="btn-gold" onclick="openModal()"><i class="fas fa-plus"></i> NEW ENTRY</button>
        </div>

        <section class="batch-card">
            <div style="font-weight:600; color:var(--navy);">Quick Semester Filter:</div>
            <div class="sem-grid">
                <?php for($i=1; $i<=8; $i++): ?>
                    <button class="sem-btn <?php echo $i==1?'active':''; ?>" onclick="loadBatch(<?php echo $i; ?>, this)">SEM <?php echo $i; ?></button>
                <?php endfor; ?>
            </div>
        </section>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Obtained</th>
                        <th>GPA</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3>Add New Result</h3>
            <form id="addForm">
                <input type="text" name="roll" placeholder="Student Roll No" required>
                <select name="sem">
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                    <option value="7">Semester 7</option>
                    <option value="8">Semester 8</option>
                </select>
                <input type="number" name="marks" placeholder="Obtained Marks" required>
                <input type="text" name="gpa" placeholder="GPA (e.g. 3.75)" required>
                <select name="status">
                    <option value="Pass">Pass</option>
                    <option value="Fail">Fail</option>
                </select>
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button type="submit" class="btn-gold" style="flex:1">SAVE RECORD</button>
                    <button type="button" onclick="closeModal()" style="flex:1; border:none; cursor:pointer;">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentSem = 1;

        async function loadBatch(sem, btn) {
            currentSem = sem;
            document.querySelectorAll('.sem-btn').forEach(b => b.classList.remove('active'));
            if(btn) btn.classList.add('active');

            const response = await fetch(`?action=load&semester=${sem}`);
            const result = await response.json();
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            result.data.forEach(row => {
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${row.student_roll_no}</strong></td>
                        <td>${row.student_name}</td>
                        <td>${row.total_marks}</td>
                        <td style="font-weight:bold">${row.gpa}</td>
                        <td><span style="color:${row.result_status=='Pass'?'green':'red'}">${row.result_status}</span></td>
                        <td>
                            <button class="btn-del" onclick="deleteRow(${row.summary_id})">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        }

        async function deleteRow(id) {
            if(!confirm("Are you sure you want to delete this record?")) return;
            const fd = new FormData(); fd.append('id', id);
            const resp = await fetch('?action=delete', { method: 'POST', body: fd });
            const res = await resp.json();
            if(res.status === 'success') loadBatch(currentSem);
        }

        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const resp = await fetch('?action=add', { method: 'POST', body: fd });
            const res = await resp.json();
            if(res.status === 'success') {
                closeModal();
                loadBatch(fd.get('sem'));
            } else { alert("Failed to add record. Ensure Roll No exists in Students table."); }
        };

        function openModal() { document.getElementById('addModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('addModal').style.display = 'none'; }
        
        window.onload = () => loadBatch(1);
    </script>
</body>
</html>
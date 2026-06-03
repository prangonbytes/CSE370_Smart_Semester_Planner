<?php
session_start();
include "DBconnect.php";

if (!isset($_SESSION['admin_name'])) {
    die("ACCESS DENIED. PLEASE LOGIN FIRST.");
}

$name = $_SESSION['admin_name'];
$admin_id = $_SESSION['admin_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
    $c_name = mysqli_real_escape_string($conn, strtoupper($_POST['course_name']));
    $c_code = mysqli_real_escape_string($conn, strtoupper($_POST['course_code']));
    $credits = mysqli_real_escape_string($conn, $_POST['credit_hours']);
    $diff = mysqli_real_escape_string($conn, $_POST['difficulty']);
    $dept_id = mysqli_real_escape_string($conn, $_POST['dept_id']); 
    

    $check = mysqli_query($conn, "SELECT * FROM COURSE WHERE course_code='$c_code'");
    
    if (mysqli_num_rows($check) > 0) {
        $message = "<div class='msg error'>FAILED TO ADD. COURSE $c_code ALREADY EXISTS.</div>";
    } else {
       
        $insert_query = "INSERT INTO COURSE (course_name, course_code, credit_hours, difficulty_level, admin_id, dept_id) 
                         VALUES ('$c_name', '$c_code', '$credits', '$diff', '$admin_id', '$dept_id')";
        
        if (mysqli_query($conn, $insert_query)) {
            $new_course_id = mysqli_insert_id($conn);
            
 
            if (!empty($_POST['prerequisites'])) {
                foreach ($_POST['prerequisites'] as $prereq_id) {
                    if ($prereq_id !== 'none') {
                        $safe_prereq = mysqli_real_escape_string($conn, $prereq_id);
                        mysqli_query($conn, "INSERT INTO PREREQUISITE (course_id, prereq_course_id) VALUES ('$new_course_id', '$safe_prereq')");
                    }
                }
            }
           
            $message = "<div class='msg success'>SUCCESSFULLY ADDED COURSE: $c_name</div>";
        } else {
            $message = "<div class='msg error'>ERROR: " . strtoupper(mysqli_error($conn)) . "</div>";
        }
    }
}


$courses_result = mysqli_query($conn, "SELECT course_id, course_code FROM COURSE ORDER BY course_code ASC");
$depts_result = mysqli_query($conn, "SELECT * FROM DEPARTMENT ORDER BY dept_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #eef2f5; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            flex-direction: column;
            min-height: 100vh; 
        }
        
        /* Full Width Top Banner */
        .header-banner { 
            background: linear-gradient(135deg, #2d6cdf, #1b4b9c); 
            color: white; 
            padding: 20px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header-banner h2 { margin: 0; font-size: 24px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        .header-banner p { margin: 0; font-size: 16px; font-weight: bold; opacity: 0.9; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px;}
        
        /* Center Content Area */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .dashboard-card { 
            background: white; 
            width: 100%;
            max-width: 800px;
            padding: 40px;
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        }

        .dashboard-card h3 { margin-top: 0; color: #333; text-align: center; margin-bottom: 30px; font-size: 22px; border-bottom: 2px solid #eef2f5; padding-bottom: 15px;}

        /* Grid Layout for Form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }

        label { font-weight: bold; color: #555; font-size: 14px; }
        input[type="text"], input[type="number"], select { 
            padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s;
        }
        input:focus, select:focus { border-color: #2d6cdf; box-shadow: 0 0 5px rgba(45, 108, 223, 0.3); }

        .prereq-box {
            height: 140px; overflow-y: auto; border: 1px solid #ccc; border-radius: 8px; padding: 12px; background: #f9f9f9;
        }
        .prereq-box label { display: block; cursor: pointer; margin-bottom: 8px; font-weight: normal; color: #333;}
        .prereq-box input[type="checkbox"] { margin-right: 10px; transform: scale(1.1); }

        /* Buttons */
        .btn-container { display: flex; gap: 20px; margin-top: 30px; }
        .btn { flex: 1; text-align: center; padding: 15px; border-radius: 8px; font-weight: bold; font-size: 16px; text-decoration: none; transition: all 0.3s ease; border: none; cursor: pointer; }
        .btn-green { background: #28a745; color: white; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2); }
        .btn-green:hover { background: #218838; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3); }
        .btn-blue { background: #f0f4ff; color: #2d6cdf; border: 2px solid #2d6cdf; }
        .btn-blue:hover { background: #2d6cdf; color: white; }

        /* System Messages */
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: bold; text-align: center; }
        .msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div class="header-banner">
        <h2>Welcome Admin, <?php echo strtoupper(htmlspecialchars($name)); ?>!</h2>
        <p>Admin ID: <?php echo htmlspecialchars($admin_id); ?></p>
    </div>

    <div class="main-content">
        <div class="dashboard-card">
            
            <?php echo $message; ?>
            
            <h3>Add New Course to Catalog</h3>
            
            <form method="POST" action="admin_dashboard.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Course Code</label>
                        <input type="text" name="course_code" placeholder="e.g. CSE110" required>
                    </div>
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" name="course_name" placeholder="e.g. PROGRAMMING LANGUAGE I" required>
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <select name="dept_id" required>
                            <option value="" disabled selected>Select Department...</option>
                            <?php while ($d = mysqli_fetch_assoc($depts_result)) { ?>
                                <option value="<?php echo $d['dept_id']; ?>"><?php echo htmlspecialchars(strtoupper($d['dept_name'])); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: row; gap: 15px;">
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <label>Credits</label>
                            <input type="number" min="1" name="credit_hours" placeholder="e.g. 3" required>
                        </div>
                        <div style="flex: 2; display: flex; flex-direction: column; gap: 8px;">
                            <label>Difficulty</label>
                            <select name="difficulty" required>
                                <option value="" disabled selected>Select...</option>
                                <option value="Easy">Easy</option>
                                <option value="Moderate">Moderate</option>
                                <option value="Difficult">Difficult</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Prerequisites (Scroll to select multiple)</label>
                        <div class="prereq-box">
                            <label><input type="checkbox" id="prereq-none" name="prerequisites[]" value="none" checked onchange="togglePrereqs(this)"> None (No Prerequisite)</label>
                            
                            <?php while ($row = mysqli_fetch_assoc($courses_result)) { ?>
                                <label>
                                    <input type="checkbox" class="prereq-course" name="prerequisites[]" value="<?php echo $row['course_id']; ?>" onchange="togglePrereqs(this)"> 
                                    <?php echo htmlspecialchars($row['course_code']); ?>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="btn-container">
                    <a href="view_courses.php" class="btn btn-blue">VIEW CATALOG</a>
                    <button type="submit" class="btn btn-green">ADD COURSE</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePrereqs(clickedCheckbox) {
            const noneBox = document.getElementById('prereq-none');
            const courseBoxes = document.querySelectorAll('.prereq-course');

            
            if (clickedCheckbox.id === 'prereq-none' && clickedCheckbox.checked) {
                courseBoxes.forEach(box => box.checked = false);
            } 
            
            else if (clickedCheckbox.classList.contains('prereq-course') && clickedCheckbox.checked) {
                noneBox.checked = false;
            }
        }
    </script>
</body>
</html>
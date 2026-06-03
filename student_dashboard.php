<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "DBconnect.php"; 

if (!isset($_SESSION['student_id'])) {
    $_SESSION['student_id'] = '1'; 
}

$sid = $_SESSION['student_id'];
$current_semester = "Spring 2026"; 

// --- THE SEMESTER MATH ENGINE ---
function getSemesterDistance($completed_sem, $current_sem) {
    if (empty($completed_sem)) return 99; 
    
    $seasons = ['Spring' => 1, 'Summer' => 2, 'Fall' => 3];
    $c_parts = explode(' ', trim($completed_sem));
    $curr_parts = explode(' ', trim($current_sem));
    
    if (count($c_parts) != 2 || count($curr_parts) != 2) return 99;
    
    $c_val = (intval($c_parts[1]) * 3) + ($seasons[$c_parts[0]] ?? 0);
    $curr_val = (intval($curr_parts[1]) * 3) + ($seasons[$curr_parts[0]] ?? 0);
    
    return $curr_val - $c_val;
}

// --- FORM PROCESSOR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_plan'])) {
    $selected_courses = intval($_POST['no_of_courses']);
    $selected_load = mysqli_real_escape_string($conn, $_POST['desired_semester_load']);

    $update_sql = "UPDATE STUDENT SET no_of_courses='$selected_courses', desired_semester_load='$selected_load' WHERE id='$sid'";
    mysqli_query($conn, $update_sql);

    $_SESSION['no_of_courses'] = $selected_courses;
    $_SESSION['desired_semester_load'] = $selected_load;
    $_SESSION['retakes'] = $_POST['retakes'] ?? []; 
    
    header("Location: degree_audit.php");
    exit();
}

$s_query = mysqli_query($conn, "SELECT * FROM STUDENT WHERE id='$sid'");
$student = mysqli_fetch_assoc($s_query);

// THE NAME FIX: Scans multiple possible column names to find your actual name
$name = $student['student_name'] ?? $student['name'] ?? $student['full_name'] ?? "STUDENT";

$cgpa = isset($student['cgpa']) ? number_format(floatval($student['cgpa']), 2) : "0.00";
$current_courses = $student['no_of_courses'] ?? 4;
$current_load = ucfirst(strtolower($student['desired_semester_load'] ?? 'Moderate'));

$retake_sql = "SELECT c.course_id, c.course_code, c.credit_hours, cc.gpa, cc.completed_semester 
               FROM COURSE_COMPLETED cc 
               JOIN COURSE c ON cc.course_id = c.course_id 
               WHERE cc.student_id = '$sid' AND cc.gpa < 3.00";
$retake_res = mysqli_query($conn, $retake_sql);

$eligible_retakes = [];
if ($retake_res) {
    while ($row = mysqli_fetch_assoc($retake_res)) {
        $distance = getSemesterDistance($row['completed_semester'], $current_semester);
        if ($distance > 0 && $distance <= 2) {
            $eligible_retakes[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; }
        .header { background-color: #2b6cb0; color: white; padding: 25px 40px; font-size: 26px; font-weight: bold; letter-spacing: 1px; }
        .dashboard-card { background: white; max-width: 650px; margin: 40px auto; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .cgpa-circle { width: 120px; height: 120px; border: 6px solid #2b6cb0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 38px; font-weight: bold; color: #333; margin: 0 auto 10px auto; }
        .cgpa-label { color: #718096; font-weight: bold; text-align: center; display: block; margin-bottom: 30px; letter-spacing: 1px; }
        .btn { display: block; width: 100%; padding: 14px; margin-bottom: 15px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; border: none; text-align: center; text-decoration: none; box-sizing: border-box;}
        .btn-outline { border: 2px solid #2b6cb0; color: #2b6cb0; background-color: transparent; }
        .btn-success { background-color: #28a745; color: white; margin-top: 20px; }
        .settings-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .settings-box h3 { margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 8px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;}
        .retake-item { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-bottom: 10px; border-radius: 4px; display: flex; align-items: center; }
        .retake-item input { margin-right: 10px; transform: scale(1.2); }
        .retake-details { font-size: 14px; color: #333; }
        .success-text { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 15px; border-radius: 5px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
    WELCOME, <?php echo htmlspecialchars(strtoupper($_SESSION['student_name'] ?? $student['student_name'] ?? "STUDENT")); ?>!
</div>
    <div class="dashboard-card">
        <div class="cgpa-circle"><?php echo $cgpa; ?></div>
        <span class="cgpa-label">CURRENT CGPA</span>
        <a href="choose_completed_courses.php" class="btn btn-outline">Manage Completed Courses</a>
        <form method="POST" action="">
            <div class="settings-box">
                <h3>Eligible Retakes (Expire after 2 Semesters)</h3>
                <?php if (empty($eligible_retakes)): ?>
                    <div class="success-text">No courses need to be retaken! All your previous grades are above 3.00 or have passed the 2-semester threshold.</div>
                <?php else: ?>
                    <?php foreach ($eligible_retakes as $course): ?>
                        <label class="retake-item">
                            <input type="checkbox" name="retakes[]" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                            <div class="retake-details">
                                <strong><?php echo htmlspecialchars($course['course_code']); ?></strong> 
                                (GPA: <?php echo htmlspecialchars($course['gpa']); ?>) <br>
                                <small>Taken in: <?php echo htmlspecialchars($course['completed_semester']); ?></small>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="settings-box">
                <h3>Semester Plan Settings</h3>
                <div class="form-group">
                    <label>Total Courses (Including Retakes)</label>
                    <select name="no_of_courses">
                        <option value="3" <?php if($current_courses == 3) echo 'selected'; ?>>3 Courses</option>
                        <option value="4" <?php if($current_courses == 4) echo 'selected'; ?>>4 Courses</option>
                        <option value="5" <?php if($current_courses == 5) echo 'selected'; ?>>5 Courses</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Desired Workload Difficulty</label>
                    <select name="desired_semester_load">
                        <option value="Low" <?php if($current_load == 'Low') echo 'selected'; ?>>Low (Easier Courses)</option>
                        <option value="Moderate" <?php if($current_load == 'Moderate') echo 'selected'; ?>>Moderate (Balanced)</option>
                        <option value="High" <?php if($current_load == 'High') echo 'selected'; ?>>High (Advanced Courses)</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="generate_plan" class="btn btn-success">Generate Semester Plan</button>
        </form>
    </div>
</body>
</html>
<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "DBconnect.php"; 

if (!isset($_SESSION['student_id'])) {
    $_SESSION['student_id'] = '1'; 
}
$sid = $_SESSION['student_id'];

$success_msg = $_SESSION['action_success'] ?? "";
$error_msg = $_SESSION['action_error'] ?? "";
unset($_SESSION['action_success']);
unset($_SESSION['action_error']);


function getSemesterValue($sem_str) {
    if (empty($sem_str)) return 0;
    $seasons = ['Spring' => 1, 'Summer' => 2, 'Fall' => 3];
    $parts = explode(' ', trim($sem_str));
    if (count($parts) != 2) return 0;
    
    return (intval($parts[1]) * 3) + ($seasons[$parts[0]] ?? 0);
}


function deleteCourseAndDependents($conn, $sid, $course_id) {
    $dep_sql = "SELECT cc.course_id FROM COURSE_COMPLETED cc
                JOIN PREREQUISITE p ON cc.course_id = p.course_id
                WHERE cc.student_id = '$sid' AND p.prereq_course_id = '$course_id'";
    $dep_res = mysqli_query($conn, $dep_sql);
    
    if ($dep_res) {
        while ($row = mysqli_fetch_assoc($dep_res)) {
            deleteCourseAndDependents($conn, $sid, $row['course_id']);
        }
    }
    
    $del_sql = "DELETE FROM COURSE_COMPLETED WHERE student_id='$sid' AND course_id='$course_id'";
    mysqli_query($conn, $del_sql);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    
    if (isset($_POST['add_course'])) {
        $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
        $course_code = mysqli_real_escape_string($conn, $_POST['course_code']); 
        $gpa = mysqli_real_escape_string($conn, $_POST['gpa']);
        $semester = mysqli_real_escape_string($conn, $_POST['completed_semester']);
        
        $new_course_val = getSemesterValue($semester); 
        
        
        $prereq_check_sql = "SELECT c2.course_code, cc.completed_semester 
                             FROM PREREQUISITE p 
                             JOIN COURSE c2 ON p.prereq_course_id = c2.course_id
                             LEFT JOIN COURSE_COMPLETED cc ON p.prereq_course_id = cc.course_id AND cc.student_id = '$sid'
                             WHERE p.course_id = '$course_id'";
                             
        $prereq_check_res = mysqli_query($conn, $prereq_check_sql);
        
        $missing_prereqs = [];
        $timeline_errors = [];

        if (mysqli_num_rows($prereq_check_res) > 0) {
            while ($p_row = mysqli_fetch_assoc($prereq_check_res)) {
                if (empty($p_row['completed_semester'])) {
                  
                    $missing_prereqs[] = $p_row['course_code'];
                } else {
                   
                    $prereq_val = getSemesterValue($p_row['completed_semester']);
                    
            
                    if ($prereq_val >= $new_course_val) {
                        $timeline_errors[] = $p_row['course_code'] . " (DONE IN " . strtoupper($p_row['completed_semester']) . ")";
                    }
                }
            }
        }

      
        if (!empty($missing_prereqs) || !empty($timeline_errors)) {
            $err_str = "FAILED TO ADD $course_code. ";
            if (!empty($missing_prereqs)) {
                $err_str .= "YOU MUST COMPLETE THE PREREQUISITE(S): " . implode(", ", $missing_prereqs) . ". ";
            }
            if (!empty($timeline_errors)) {
           
                $err_str .= "TIMELINE ERROR: PREREQUISITES CANNOT BE TAKEN AFTER OR DURING THIS COURSE. INVALID TIMELINES: " . implode(", ", $timeline_errors) . ".";
            }
            
            $_SESSION['action_error'] = strtoupper($err_str);
        } else {
           
            if (!empty($gpa) && !empty($semester)) {
                $insert_sql = "INSERT IGNORE INTO COURSE_COMPLETED (student_id, course_id, gpa, completed_semester) 
                               VALUES ('$sid', '$course_id', '$gpa', '$semester')";
                if (mysqli_query($conn, $insert_sql)) {
                    $_SESSION['action_success'] = strtoupper("$course_code SUCCESSFULLY ADDED.");
                }
            }
        }
        header("Location: choose_completed_courses.php");
        exit();
    }
    
    
    if (isset($_POST['undo_course'])) {
        $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
        $course_code = mysqli_real_escape_string($conn, $_POST['course_code']);
        
        deleteCourseAndDependents($conn, $sid, $course_id); 
        
        $_SESSION['action_success'] = strtoupper("$course_code (AND ANY DEPENDENT COURSES) SUCCESSFULLY REMOVED.");
        header("Location: choose_completed_courses.php");
        exit();
    }
}

$sql = "SELECT c.*, cc.gpa, cc.completed_semester,
        (SELECT GROUP_CONCAT(c2.course_code SEPARATOR ', ') 
         FROM PREREQUISITE p 
         JOIN COURSE c2 ON p.prereq_course_id = c2.course_id 
         WHERE p.course_id = c.course_id) AS prereq_string
        FROM COURSE c 
        LEFT JOIN COURSE_COMPLETED cc ON c.course_id = cc.course_id AND cc.student_id = '$sid'
        ORDER BY c.course_code ASC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Completed Courses</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f7fa; margin: 0; padding: 40px; }
        .container { max-width: 1150px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-back { background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #333; color: white; padding: 15px; text-align: left; }
        td { padding: 15px; border-bottom: 1px solid #ddd; vertical-align: middle; }
        
        .prereq-text { color: #6c757d; font-style: italic; font-size: 14px;}
        .grade-text { font-weight: bold; color: #28a745; }
        .semester-text { color: #555; font-weight: 500; }
        
        .btn { padding: 8px 16px; font-weight: bold; border-radius: 5px; border: none; cursor: pointer; color: white;}
        .btn-add { background-color: #28a745; }
        .btn-undo { background-color: #dc3545; }
        
        select { padding: 8px; border: 1px solid #ccc; border-radius: 5px; width: 100%; min-width: 120px; box-sizing: border-box;}
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; text-align: center; border: 1px solid transparent; }
        .alert-error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Manage Completed Courses</h2>
        <a href="student_dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Course Code</th>
            <th>Course Name</th>
            <th>Prerequisites</th>
            <th>Grade (GPA)</th>
            <th>Semester</th>
            <th>Action</th>
        </tr>
        
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['course_code']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['course_name'] ?? ''); ?></td>
                <td class="prereq-text"><?php echo htmlspecialchars($row['prereq_string'] ?? 'None'); ?></td>
                
                <?php if (!empty($row['gpa'])): ?>
                    <td class="grade-text"><?php echo htmlspecialchars($row['gpa']); ?></td>
                    <td class="semester-text"><?php echo htmlspecialchars(strtoupper($row['completed_semester'])); ?></td>
                    <td>
                        <form method="POST" action="" style="margin: 0;">
                            <input type="hidden" name="course_id" value="<?php echo $row['course_id']; ?>">
                            <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($row['course_code']); ?>">
                            <button type="submit" name="undo_course" class="btn btn-undo">UNDO</button>
                        </form>
                    </td>
                <?php else: ?>
                    <form method="POST" action="" id="add_form_<?php echo $row['course_id']; ?>" style="margin: 0; display:none;">
                        <input type="hidden" name="course_id" value="<?php echo $row['course_id']; ?>">
                        <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($row['course_code']); ?>">
                    </form>
                    
                    <td>
                        <select name="gpa" required form="add_form_<?php echo $row['course_id']; ?>">
                            <option value="">Select GPA</option>
                            <option value="4.00">4.00</option>
                            <option value="3.70">3.70</option>
                            <option value="3.30">3.30</option>
                            <option value="3.00">3.00</option>
                            <option value="2.70">2.70</option>
                            <option value="2.30">2.30</option>
                            <option value="2.00">2.00</option>
                            <option value="1.70">1.70</option>
                            <option value="1.30">1.30</option>
                            <option value="1.00">1.00</option>
                            <option value="0.00">0.00 (F)</option>
                        </select>
                    </td>
                    <td>
                        <select name="completed_semester" required form="add_form_<?php echo $row['course_id']; ?>">
                            <option value="">Select Semester</option>
                            <option value="Spring 2026">Spring 2026</option>
                            <option value="Fall 2025">Fall 2025</option>
                            <option value="Summer 2025">Summer 2025</option>
                            <option value="Spring 2025">Spring 2025</option>
                            <option value="Fall 2024">Fall 2024</option>
                            <option value="Summer 2024">Summer 2024</option>
                            <option value="Spring 2024">Spring 2024</option>
                        </select>
                    </td>
                    <td>
                        <button type="submit" name="add_course" form="add_form_<?php echo $row['course_id']; ?>" class="btn btn-add">ADD</button>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
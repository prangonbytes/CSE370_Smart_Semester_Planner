<?php
session_start();
include "DBconnect.php";

if (!isset($_SESSION['admin_name'])) {
    die("Access denied.");
}

$course_id = $_GET['id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $c_name = $_POST['course_name'];
    $c_code = $_POST['course_code'];
    $credits = $_POST['credit_hours'];
    $diff = $_POST['difficulty'];
    $dept_id = $_POST['dept_id'];

   
    mysqli_query($conn, "UPDATE COURSE SET course_name='$c_name', course_code='$c_code', credit_hours='$credits', difficulty_level='$diff', dept_id='$dept_id' WHERE course_id='$course_id'");
    
   
    mysqli_query($conn, "DELETE FROM PREREQUISITE WHERE course_id='$course_id'");
    
    if (!empty($_POST['prerequisites'])) {
        foreach ($_POST['prerequisites'] as $prereq_id) {
            if ($prereq_id !== 'none') {
                mysqli_query($conn, "INSERT INTO PREREQUISITE (course_id, prereq_course_id) VALUES ('$course_id', '$prereq_id')");
            }
        }
    }
    
    header("Location: view_courses.php");
    exit();
}


$result = mysqli_query($conn, "SELECT * FROM COURSE WHERE course_id='$course_id'");
$course = mysqli_fetch_assoc($result);


$depts_result = mysqli_query($conn, "SELECT * FROM DEPARTMENT");


$all_courses = mysqli_query($conn, "SELECT course_id, course_code FROM COURSE WHERE course_id != '$course_id'");


$current_prereqs_query = mysqli_query($conn, "SELECT prereq_course_id FROM PREREQUISITE WHERE course_id='$course_id'");
$current_prereqs = [];
while($row = mysqli_fetch_assoc($current_prereqs_query)){
    $current_prereqs[] = $row['prereq_course_id'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 0 15px rgba(0,0,0,0.1); width: 320px; text-align: center;}
        input, select { width: 90%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; }
        select[multiple] { height: 80px; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .cancel { display: block; margin-top: 15px; color: gray; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

    <div class="box">
        <h2>Update Course</h2>
        <form method="POST">
            <input type="text" name="course_name" value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
            <input type="text" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>" required>
            
            <input type="number" min="1" name="credit_hours" value="<?php echo (int)$course['credit_hours']; ?>" required>
            
            <select name="dept_id" required>
                <option value="" disabled>Department</option>
                <?php while ($d = mysqli_fetch_assoc($depts_result)) { ?>
                    <option value="<?php echo $d['dept_id']; ?>" <?php if($course['dept_id'] == $d['dept_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($d['dept_name']); ?>
                    </option>
                <?php } ?>
            </select>

            <select name="difficulty" required>
                <option value="Easy" <?php if($course['difficulty_level'] == 'Easy') echo 'selected'; ?>>Easy</option>
                <option value="Moderate" <?php if($course['difficulty_level'] == 'Moderate') echo 'selected'; ?>>Moderate</option>
                <option value="Difficult" <?php if($course['difficulty_level'] == 'Difficult') echo 'selected'; ?>>Difficult</option>
            </select>

            <select name="prerequisites[]" multiple title="Hold CTRL to select multiple">
                <option value="none" <?php if(empty($current_prereqs)) echo 'selected'; ?>>None (No Prereq)</option>
                <?php while ($c = mysqli_fetch_assoc($all_courses)) { ?>
                    <option value="<?php echo $c['course_id']; ?>" <?php if(in_array($c['course_id'], $current_prereqs)) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($c['course_code']); ?>
                    </option>
                <?php } ?>
            </select>

            <button type="submit">SAVE CHANGES</button>
        </form>
        <a href="view_courses.php" class="cancel">Cancel</a>
    </div>

</body>
</html>
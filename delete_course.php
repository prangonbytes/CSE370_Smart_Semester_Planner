<?php
session_start();
include "DBconnect.php";

if (!isset($_SESSION['admin_name'])) {
    die("Access denied.");
}

if (isset($_GET['id'])) {
    $course_id = $_GET['id'];
    

    $check_prereq = mysqli_query($conn, "SELECT * FROM PREREQUISITE WHERE prereq_course_id='$course_id'");
    
    if (mysqli_num_rows($check_prereq) > 0) {
        
        $_SESSION['error_msg'] = "Cannot delete: This course is a prerequisite for another course!";
    } else {
       
        mysqli_query($conn, "DELETE FROM COURSE WHERE course_id='$course_id'");
        $_SESSION['success_msg'] = "Course successfully removed.";
    }
}

header("Location: view_courses.php");
exit();
?>
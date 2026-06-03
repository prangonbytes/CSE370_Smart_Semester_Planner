<?php
session_start();
include "DBconnect.php";

$role = $_POST['role'] ?? "";

// Clear old errors
unset($_SESSION['id_error']);
unset($_SESSION['email_error']);

// Standard inputs
$id = mysqli_real_escape_string($conn, trim($_POST['id']));
$name = mysqli_real_escape_string($conn, strtoupper(trim($_POST['name']))); // Force name to Uppercase
$email = mysqli_real_escape_string($conn, trim($_POST['email']));

$valid = true;

// 1. Validation Logic
if (!preg_match("/^\d{8}$/", $id)) {
    $_SESSION['id_error'] = ucfirst($role) . " ID MUST BE 8 DIGITS";
    $valid = false;
}

if ($role === "student" && !preg_match("/^[a-zA-Z0-9._%+-]+@g\.bracu\.ac\.bd$/", $email)) {
    $_SESSION['email_error'] = "EMAIL MUST END WITH @G.BRACU.AC.BD";
    $valid = false;
}

if (!$valid) {
    header("Location: login.php");
    exit();
}

// 2. Insert into Master USER table (Recursive Protection)
$query_user = "INSERT IGNORE INTO USER (id, name, email) VALUES ('$id', '$name', '$email')";
mysqli_query($conn, $query_user);

if ($role === "student") {
    // Check if student already exists
    $check_student = mysqli_query($conn, "SELECT * FROM STUDENT WHERE id='$id'");
    
    if(mysqli_num_rows($check_student) > 0) {
        // Log in existing student
        $_SESSION['student_name'] = $name;
        $_SESSION['student_id'] = $id;
        header("Location: student_dashboard.php");
        exit();
    } else {
        // Register NEW student with default values
        // Note: cgpa is captured from form, workload is set to defaults for first-time use
        $cgpa = mysqli_real_escape_string($conn, $_POST['cgpa']);
        
        $query_student = "INSERT INTO STUDENT (id, no_of_courses, cgpa, desired_semester_load) 
                          VALUES ('$id', 4, '$cgpa', 'Moderate')";
        
        if(mysqli_query($conn, $query_student)) {
            $_SESSION['student_name'] = $name;
            $_SESSION['student_id'] = $id;
            header("Location: student_dashboard.php");
            exit();
        } else {
            echo "DATABASE ERROR: " . strtoupper(mysqli_error($conn));
        }
    }

} elseif ($role === "admin") {
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Check if admin already exists
    $check_admin = mysqli_query($conn, "SELECT * FROM ADMIN WHERE id='$id'");
    
    if(mysqli_num_rows($check_admin) > 0) {
        $_SESSION['admin_name'] = $name;
        $_SESSION['admin_id'] = $id;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Register NEW admin
        $query_admin = "INSERT INTO ADMIN (id, password) VALUES ('$id', '$password')";
        
        if(mysqli_query($conn, $query_admin)) {
            $_SESSION['admin_name'] = $name;
            $_SESSION['admin_id'] = $id;
            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "DATABASE ERROR: " . strtoupper(mysqli_error($conn));
        }
    }

} else {
    echo "INVALID ROLE DETECTED!";
}
?>
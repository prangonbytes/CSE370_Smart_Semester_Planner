<?php
session_start();
include "DBconnect.php";

if (!isset($_SESSION['admin_name'])) {
    die("Access denied.");
}


$error_msg = "";
$success_msg = "";

if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}


$query = "SELECT c.*, d.dept_name, GROUP_CONCAT(pc.course_code SEPARATOR ', ') as prereqs 
          FROM COURSE c 
          LEFT JOIN PREREQUISITE p ON c.course_id = p.course_id 
          LEFT JOIN COURSE pc ON p.prereq_course_id = pc.course_id 
          LEFT JOIN DEPARTMENT d ON c.dept_id = d.dept_id
          GROUP BY c.course_id
          ORDER BY d.dept_name ASC, c.course_code ASC"; 

          
$result = mysqli_query($conn, $query);

$serial_no = 1; 
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Courses</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 40px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #333; color: white; }
        
        /* Highlight the department grouping slightly */
        td.dept-col { font-weight: bold; color: #2d6cdf; }
        
        a.btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 14px; color: white; margin-right: 5px;}
        .btn-edit { background: #28a745; }
        .btn-delete { background: #dc3545; }
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #2d6cdf; color: white; text-decoration: none; border-radius: 5px;}
        
        /* Stylish Alert Boxes */
        .msg-error { background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #f5c6cb; font-weight: bold; }
        .msg-success { background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb; font-weight: bold; }
    </style>
</head>
<body>

    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    
    <?php if (!empty($error_msg)): ?>
        <div class="msg-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="msg-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <h2>All Added Courses</h2>

    <table>
        <tr>
            <th>Serial No.</th>
            <th>Department</th>
            <th>Course Code</th>
            <th>Course Name</th>
            <th>Credits</th>
            <th>Difficulty</th>
            <th>Prerequisites</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo $serial_no++; ?></td>
            <td class="dept-col"><?php echo htmlspecialchars($row['dept_name'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
            <td><?php echo $row['credit_hours']; ?></td>
            <td><?php echo $row['difficulty_level']; ?></td>
            <td>
                <?php echo $row['prereqs'] ? htmlspecialchars($row['prereqs']) : '<em>None</em>'; ?>
            </td>
            <td>
                <a href="edit_course.php?id=<?php echo $row['course_id']; ?>" class="btn btn-edit">Update</a>
                <a href="delete_course.php?id=<?php echo $row['course_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this?');">Remove</a>
            </td>
        </tr>
        <?php } ?>
    </table>

</body>
</html>
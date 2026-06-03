<?php
session_start();
include "DBconnect.php"; 

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$sid = $_SESSION['student_id'];

// Calculate Total Curriculum Requirements per Department
$total_sql = "SELECT d.dept_name, SUM(c.credit_hours) as total 
              FROM COURSE c 
              JOIN DEPARTMENT d ON c.dept_id = d.dept_id 
              GROUP BY d.dept_name";
$total_res = mysqli_query($conn, $total_sql);

$stats = [];
while($row = mysqli_fetch_assoc($total_res)) {
    $stats[$row['dept_name']] = ['total' => $row['total'], 'earned' => 0];
}

// Earned Credits from the student's completion history
$earned_sql = "SELECT d.dept_name, SUM(c.credit_hours) as earned 
               FROM COURSE_COMPLETED cc 
               JOIN COURSE c ON cc.course_id = c.course_id 
               JOIN DEPARTMENT d ON c.dept_id = d.dept_id 
               WHERE cc.student_id = '$sid' 
               GROUP BY d.dept_name";

$earned_res = mysqli_query($conn, $earned_sql);
while($row = mysqli_fetch_assoc($earned_res)) {
    $stats[$row['dept_name']]['earned'] = $row['earned'];
}

// --- NEW LOGIC: Calculate Deficits and Send to Feature 1 ---
$deficits = [];
foreach($stats as $name => $data) {
    if ($data['earned'] < $data['total']) {
        $deficits[] = $name; // Flag this department as incomplete
    }
}
$_SESSION['deficits'] = $deficits; // Save state for the Load Balancer
?>

<!DOCTYPE html>
<html>
<head>
    <title>Degree Audit</title>
    <style>
        body { font-family: 'Segoe UI', Arial; background: #f5f7fa; padding: 40px; }
        .audit-container { background: white; padding: 30px; border-radius: 15px; max-width: 600px; margin: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .progress-row { margin-bottom: 20px; }
        .bar-bg { background: #e9ecef; height: 12px; border-radius: 6px; margin-top: 5px; overflow: hidden; }
        .bar-fill { background: #28a745; height: 100%; transition: width 0.6s ease; }
        .label-group { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; }
        .btn-proceed { display: block; text-align: center; margin-top: 25px; padding: 12px; background: #0d6efd; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="audit-container">
        <h2 style="text-align: center; color: #333;">Degree Progress Audit</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">Analyzing credits earned by category</p>

        <?php foreach($stats as $name => $data): 
            $percent = ($data['total'] > 0) ? ($data['earned'] / $data['total']) * 100 : 0;
            if($percent > 100) $percent = 100; // Cap at 100% visually
        ?>
            <div class="progress-row">
                <div class="label-group">
                    <span><?php echo htmlspecialchars($name); ?></span>
                    <span><?php echo $data['earned']; ?> / <?php echo $data['total']; ?> Cr</span>
                </div>
                <div class="bar-bg">
                    <div class="bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Direct pipeline to Feature 1 -->
        <a href="load_balancer.php" class="btn-proceed">Generate Next Semester Schedule</a>
    </div>
</body>
</html>
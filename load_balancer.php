<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "DBconnect.php"; 

unset($_SESSION['no_of_courses']);
unset($_SESSION['desired_semester_load']);

$sid = $_SESSION['student_id'] ?? '1';
$deficits = $_SESSION['deficits'] ?? [];
$requested_retakes = $_SESSION['retakes'] ?? []; 

$s_query = mysqli_query($conn, "SELECT * FROM STUDENT WHERE id='$sid'");
$student = mysqli_fetch_assoc($s_query);

$target_courses = !empty($student['no_of_courses']) ? intval($student['no_of_courses']) : 4;
$raw_load = !empty($student['desired_semester_load']) ? $student['desired_semester_load'] : 'high';
$load_pref = strtolower(trim($raw_load));

// =========================================================================
// FEATURE 3: DEPARTMENT-SPECIFIC PERFORMANCE & RISK ANALYTICS ENGINE
// =========================================================================
$performance_stats = [];

$risk_sql = "SELECT d.dept_name, AVG(cc.gpa) as dept_avg_gpa 
             FROM COURSE_COMPLETED cc 
             JOIN COURSE c ON cc.course_id = c.course_id 
             JOIN DEPARTMENT d ON c.dept_id = d.dept_id 
             WHERE cc.student_id = '$sid' 
             GROUP BY d.dept_name";
             
$risk_res = mysqli_query($conn, $risk_sql);

if ($risk_res) {
    while ($row = mysqli_fetch_assoc($risk_res)) {
        $avg_gpa = floatval($row['dept_avg_gpa']);
        $performance_stats[$row['dept_name']] = $avg_gpa; 
    }
}
// =========================================================================

$final_recommendations = [];
$current_credits = 0;

// --- STEP 1: FORCE REQUESTED RETAKES INTO THE SCHEDULE FIRST ---
if (!empty($requested_retakes)) {
    $clean_ids = array_map('intval', $requested_retakes);
    $retake_id_string = implode(",", $clean_ids);
    
    $retake_sql = "SELECT c.*, d.dept_name,
                   CASE 
                        WHEN c.difficulty_level IN ('Hard', 'High', 'Difficult', '5', '4', '3') THEN 3
                        WHEN c.difficulty_level IN ('Moderate', 'Medium', '2') THEN 2
                        WHEN c.difficulty_level IN ('Easy', 'Low', '1') THEN 1
                        ELSE 0 
                   END as diff_weight
                   FROM COURSE c 
                   LEFT JOIN DEPARTMENT d ON c.dept_id = d.dept_id 
                   WHERE c.course_id IN ($retake_id_string)";
                   
    $retake_res = mysqli_query($conn, $retake_sql);
    
    while ($r_row = mysqli_fetch_assoc($retake_res)) {
        $r_row['is_retake'] = true; 
        $final_recommendations[] = $r_row;
        $current_credits += intval($r_row['credit_hours'] ?? 3);
        $target_courses--; 
    }
}

// --- STEP 2: FILL THE REST WITH DYNAMIC, RANDOMIZED COURSES ---
if ($target_courses > 0) {
    
    // THE NEW BALANCED SORTING LOGIC
    if ($load_pref == 'low' || $load_pref == 'easy') {
        $diff_filter = "AND c.difficulty_level IN ('1', '2', 'Low', 'Easy')";
        $order_by_clause = "diff_weight ASC, RAND()"; 
        
    } elseif ($load_pref == 'high' || $load_pref == 'difficult' || $load_pref == 'hard') {
        $diff_filter = ""; 
        $order_by_clause = "diff_weight DESC, RAND()"; 
        
    } else {
        // MODERATE: The "Trail Mix" approach. Allow all difficulties!
        // ABS(diff_weight - 2) anchors Moderate courses first, then RAND() perfectly mixes Easy and Hard.
        $diff_filter = ""; 
        $order_by_clause = "ABS(diff_weight - 2) ASC, RAND()"; 
    }

    $deficit_string = empty($deficits) ? "''" : "'" . implode("','", $deficits) . "'";

    $sql = "SELECT c.*, d.dept_name,
            CASE 
                WHEN c.difficulty_level IN ('Hard', 'High', 'Difficult', '5', '4', '3') THEN 3
                WHEN c.difficulty_level IN ('Moderate', 'Medium', '2') THEN 2
                WHEN c.difficulty_level IN ('Easy', 'Low', '1') THEN 1
                ELSE 0 
            END as diff_weight
            FROM COURSE c
            LEFT JOIN DEPARTMENT d ON c.dept_id = d.dept_id
            WHERE c.course_id NOT IN (SELECT course_id FROM COURSE_COMPLETED WHERE student_id = '$sid')
            $diff_filter
            AND NOT EXISTS (
                SELECT 1 FROM PREREQUISITE p 
                WHERE p.course_id = c.course_id 
                AND p.prereq_course_id NOT IN (SELECT course_id FROM COURSE_COMPLETED WHERE student_id = '$sid')
            )
            ORDER BY 
                CASE WHEN d.dept_name IN ($deficit_string) THEN 0 ELSE 1 END, 
                $order_by_clause"; 

    $result = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['is_retake'] = false;
        $final_recommendations[] = $row;
        $current_credits += intval($row['credit_hours'] ?? 3);
        
        if (count($final_recommendations) >= (count($requested_retakes) + $target_courses)) {
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Intelligent Load Balancer</title>
    <style>
        #preloader { display: none !important; opacity: 0 !important; visibility: hidden !important; z-index: -999 !important; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f7fa; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 15px; max-width: 950px; margin: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        
        /* Analytics Panel Styles */
        .analytics-panel { background: #e9ecef; padding: 20px; border-radius: 10px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 15px; border-left: 5px solid #6c757d; }
        .stat-box { background: white; padding: 10px 15px; border-radius: 6px; border: 1px solid #dee2e6; flex: 1; min-width: 150px; text-align: center; }
        .stat-name { font-size: 12px; color: #6c757d; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;}
        .stat-val { font-size: 20px; font-weight: bold; color: #212529; }
        .val-danger { color: #dc3545; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #0d6efd; color: white; padding: 12px; text-align: left; }
        td { border-bottom: 1px solid #ddd; padding: 12px; vertical-align: middle; }
        
        .badge-container { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; }
        .tag { font-size: 0.8em; padding: 6px 10px; border-radius: 6px; font-weight: bold; display: inline-block; text-align: center; width: 100%; box-sizing: border-box; }
        
        /* Priority Tags */
        .tag-priority { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db;}
        .tag-deficit { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;}
        .tag-retake { background: #e2d9f3; color: #4b0082; border: 1px solid #d0bdf0;}
        
        /* Risk Tags */
        .tag-high-risk { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .tag-mod-risk { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .tag-low-risk { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;}
    </style>
</head>
<body>
    <div class="card">
        <h2 style="color: #333; margin-top: 0;">Semester Load Balancer</h2>
        
        <?php if(!empty($performance_stats)): ?>
        <div class="analytics-panel">
            <div style="width: 100%; font-weight: bold; margin-bottom: 5px; color: #495057;">Department Performance Analytics</div>
            <?php foreach($performance_stats as $dept => $avg): ?>
                <div class="stat-box">
                    <div class="stat-name"><?php echo htmlspecialchars($dept); ?> AVG</div>
                    <div class="stat-val <?php echo ($avg < 3.00) ? 'val-danger' : ''; ?>">
                        <?php echo number_format($avg, 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <p style="color: #555; margin-bottom: 20px; font-size: 16px;">
            Targeting <b><?php echo count($final_recommendations); ?> courses</b> based on your workload preferences.
        </p>

        <table>
            <tr><th>Course</th><th>Difficulty</th><th>Credits</th><th>Dept</th><th>Status</th></tr>
            <?php if(empty($final_recommendations)): ?>
                <tr><td colspan="5" style="text-align:center; color:red; font-weight:bold; padding: 20px;">No eligible courses match this criteria.</td></tr>
            <?php else: ?>
                <?php foreach($final_recommendations as $course): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($course['course_code'] ?? 'N/A'); ?></strong></td>
                    <td><?php echo htmlspecialchars($course['difficulty_level'] ?? '?'); ?></td>
                    <td><?php echo htmlspecialchars($course['credit_hours'] ?? '3'); ?></td>
                    <td><?php echo htmlspecialchars($course['dept_name'] ?? 'N/A'); ?></td>
                    <td>
                        <div class="badge-container">
                            <?php 
                                // 1. PRIORITY TAG
                                if(!empty($course['is_retake'])) {
                                    echo '<span class="tag tag-retake">Priority: Retake</span>';
                                } elseif(isset($course['dept_name']) && in_array($course['dept_name'], $deficits)) {
                                    echo '<span class="tag tag-deficit">Priority: Degree Deficit</span>';
                                } else {
                                    echo '<span class="tag tag-priority">Balanced Pick</span>';
                                }
                                
                                // 2. THE STRICT DOMAIN PENALTY RISK LOGIC
                                $dept_name = $course['dept_name'] ?? '';
                                $course_diff = intval($course['diff_weight'] ?? 1); 
                                
                                if (isset($performance_stats[$dept_name])) {
                                    $dept_avg = $performance_stats[$dept_name];
                                    
                                    if ($dept_avg < 3.00) {
                                        // DOMAIN WEAKNESS: STRICT HIGH RISK REGARDLESS OF DIFFICULTY
                                        echo '<span class="tag tag-high-risk">🚨 High Risk (History of Low GPA in Dept)</span>';
                                    } else {
                                        // STRONG DEPARTMENT
                                        if ($course_diff >= 3) {
                                            echo '<span class="tag tag-mod-risk">⚠️ Moderate Risk (Hard Course, but Strong Dept)</span>';
                                        } else {
                                            echo '<span class="tag tag-low-risk">✅ Low Risk (Strong Dept)</span>';
                                        }
                                    }
                                } else {
                                    // NO HISTORY IN THIS DEPARTMENT
                                    if ($course_diff >= 3) {
                                        echo '<span class="tag tag-mod-risk">⚠️ Moderate Risk (Hard Course)</span>';
                                    } else {
                                        echo '<span class="tag tag-low-risk">✅ Low Risk</span>';
                                    }
                                }
                            ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
        <p style="margin-top: 20px; font-size: 18px;"><strong>Total Semester Load: <?php echo $current_credits; ?> Credits</strong></p>
        
        <div style="display: flex; gap: 15px;">
            <a href="student_dashboard.php" class="btn-back">← Back to Dashboard</a>
            <a href="load_balancer.php" class="btn-back" style="background: #0d6efd;">🔄 Generate Alternative Schedule</a>
        </div>
    </div>
</body>
</html>
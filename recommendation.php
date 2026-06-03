<?php
session_start();
if (!isset($_SESSION['student_id'])) die("Access denied.");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Processing Recommendation</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        h1 { color: #2d6cdf; text-align: center; }
    </style>
</head>
<body>
    <h1>PLEASE WAIT... <br><br>WE ARE GETTING YOUR RECOMMENDATIONS ⚙️</h1>
</body>
</html>
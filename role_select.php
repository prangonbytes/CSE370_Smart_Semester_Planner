<?php
session_start();
session_destroy();
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Role</title>

    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial;
            background: #f5f5f5;
        }

        .box {
            text-align: center;
            background: white;
            padding: 60px 80px;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        h2 {
            font-size: 28px;
            margin-bottom: 30px;
        }

        button {
            width: 220px;
            padding: 14px;
            font-size: 18px;
            margin: 10px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
        }

        button[value="student"] {
            background: #2d6cdf;
            color: white;
        }

        button[value="admin"] {
            background: #333;
            color: white;
        }
    </style>
</head>

<body>

<div class="box">

    <h2>Select Your Role</h2>

    <form action="login.php" method="POST">
        <button type="submit" name="role" value="student">Student</button>
        <button type="submit" name="role" value="admin">Admin</button>
    </form>

</div>

</body>
</html>
<?php
include "DBconnect.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Semester Planner</title>

    <style>
        body {
            margin: 0;
            font-family: Arial;
            background: #f5f5f5;
        }

        /* TOP LEFT CONNECTION MESSAGE */
        .connection {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 12px;
            color: gray;
        }

        /* PERFECT CENTER CONTAINER */
        .container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .box {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.2);

            /* IMPORTANT: ensures visual perfect center feel */
            transform: translateY(-10px);
        }

        h1 {
            margin-bottom: 20px;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>
</head>

<body>

<div class="connection">
    
</div>

<div class="container">
    <div class="box">
        <h1>Smart Semester Planner</h1>
        <a href="role_select.php">Click to continue</a>
    </div>
</div>

</body>
</html>
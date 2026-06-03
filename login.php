<?php
session_start();


if (isset($_POST['role'])) {
    $_SESSION['role'] = $_POST['role'];
}

$role = $_SESSION['role'] ?? "";


$id_error = $_SESSION['id_error'] ?? "";
$email_error = $_SESSION['email_error'] ?? "";


unset($_SESSION['id_error']);
unset($_SESSION['email_error']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup / Login</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial;
            background: #f5f5f5;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            text-align: center;
            width: 350px;
        }
        .error {
            color: red;
            font-size: 13px;
            margin-top: 4px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        input, select {
            width: 90%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background: #333;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 15px;
        }
        button:hover {
            background: #555;
        }
    </style>
</head>
<body>

<div class="container">

<h2><?php echo ucfirst($role); ?> Form</h2>

<form action="signin.php" method="POST">

    <input type="hidden" name="role" value="<?php echo $role; ?>">

    <label>ID:</label><br>
    <input type="text" name="id" required><br>
    <div class="error"><?php echo $id_error; ?></div>

    <label>Name:</label><br>
    <input type="text" name="name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br>
    <div class="error"><?php echo $email_error; ?></div>

    <?php if ($role === "student") { ?>

        <label>CGPA:</label><br>
        <input type="number" step="0.01" name="cgpa" required><br><br>

        <label>Number of Courses to Take:</label><br>
        <input type="number" name="no_of_courses" min="3" max="5" required><br><br>

        <label>Desired Course Load:</label><br>
        <select name="course_load" required>
            <option value="Low">Low</option>
            <option value="Moderate">Moderate</option>
            <option value="High">High</option>
        </select>

    <?php } elseif ($role === "admin") { ?>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

    <?php } else { ?>
        <p style="color:red;">Role not selected</p>
    <?php } ?>

    <button type="submit">Submit</button>

</form>

</div>

</body>
</html>
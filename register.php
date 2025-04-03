<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; 
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $phone]);

    if ($stmt->rowCount() > 0) {
        echo "<p class='error-message'>Email or Phone Number already registered!</p>";
    } else {
        $sql = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$name, $email, $phone, $password, $role])) {
            echo "<p class='success-message'>Registration successful! <a href='login.php'>Login here</a></p>";
        } else {
            echo "<p class='error-message'>Registration failed!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            overflow: hidden;
            position: relative;
        }
        .container {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }
        .container:hover {
            transform: translateY(-5px);
        }
        h2 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
        }
        input, select {
            width: 100%;
            padding: 0.8rem;
            margin: 0.8rem 0;
            border-radius: 8px;
            border: 1px solid #dfe6e9;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        input:focus, select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        button {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background-color: #3498db;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }
        button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.4);
        }
        p {
            margin-top: 1.2rem;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        a {
            text-decoration: none;
            color: #3498db;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        .background-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(52, 152, 219, 0.1) 2px, transparent 2px);
            background-size: 30px 30px;
            z-index: 0;
            pointer-events: none;
        }
        .error-message {
            color: #e74c3c;
            margin: 0.5rem 0;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .success-message {
            color: #2ecc71;
            margin: 0.5rem 0;
            font-size: 0.9rem;
            font-weight: 500;
        }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.8rem center;
            background-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    <div class="container">
        <h2>Create Account</h2>
        <form method="post">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Sign in</a></p>
    </div>
</body>
</html>











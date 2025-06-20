<?php
include '../includes/db_connect.php';
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, stall_name FROM seller WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $name, $hashedPassword, $stall_name);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['seller_id'] = $id;
                $_SESSION['seller_name'] = $name;
                $_SESSION['stall_name'] = $stall_name;
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No seller found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Login - EZ-ORDER</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Icons (Font Awesome) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #186479;
            --primary-hover: #134d5d;
            --primary-light: rgba(24, 100, 121, 0.1);
            --text-color: #2d3748;
            --border-color: #e2e8f0;
            --background-light: #f5f7fa;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            height: 100vh;
            background: var(--background-light);
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 900px;
            margin: auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .left-panel {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 20px;
            text-align: center;
            width: 40%;
        }

        .left-panel img {
            width: 120px;
            margin-bottom: 20px;
        }

        .left-panel h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .left-panel p {
            font-size: 16px;
            opacity: 0.9;
        }

        .right-panel {
            padding: 40px 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 12px 40px 12px 40px;
            font-size: 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
            outline: none;
        }

        .form-group i {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            z-index: 1;
        }

        .form-group i:first-of-type {
            left: 12px;
        }

        .form-group .toggle-password {
            right: 12px;
            left: auto;
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 100, 121, 0.3);
        }

        button:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(24, 100, 121, 0.2);
        }

        button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        button:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        .bottom-text {
            margin-top: 20px;
            text-align: center;
        }

        .bottom-text a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .bottom-text a:hover {
            color: var(--primary-hover);
        }

        .error-message {
            background-color: #FED7D7;
            color: #C53030;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #FC8181;
        }

        @media screen and (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .left-panel {
                width: 100%;
            }

            .right-panel {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="left-panel">
        <img src="assets/logo.png" alt="EZ-ORDER Logo">
        <h1>EZ-ORDER</h1>
        <p>Order. Grab. Eat</p>
    </div>
    <div class="right-panel">
        <h2>Sign In</h2>
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error) echo "<p>$error</p>"; ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>
            <button type="submit">SIGN IN</button>
        </form>
        <div class="bottom-text">
            Don't have an account? <a href="register.php">Sign Up</a>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const pwd = document.getElementById("password");
        const eye = document.querySelector(".toggle-password");
        if (pwd.type === "password") {
            pwd.type = "text";
            eye.classList.remove("fa-eye");
            eye.classList.add("fa-eye-slash");
        } else {
            pwd.type = "password";
            eye.classList.remove("fa-eye-slash");
            eye.classList.add("fa-eye");
        }
    }
</script>

</body>
</html>

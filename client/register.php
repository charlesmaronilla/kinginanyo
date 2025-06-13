<?php
include '../includes/db_connect.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $email, $hashedPassword);
            
            if ($stmt->execute()) {
                header("Location: login.php?registered=true");
                exit();
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - EZ-ORDER</title>
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
            min-height: 100vh;
            background: var(--background-light);
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-height: 90vh;
            margin: 20px;
        }

        .left-panel {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 20px;
            text-align: center;
            width: 40%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
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
            margin-bottom: 24px;
            position: relative;
            width: 100%;
        }

        .form-group input {
            width: 100%;
            padding: 14px 60px 14px 50px;
            font-size: 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            transition: all 0.3s ease;
            background-color: #f8fafc;
            color: var(--text-color);
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--primary-light);
            outline: none;
            background-color: white;
        }

        .form-group input::placeholder {
            color: #94a3b8;
            font-size: 14px;
        }

        .form-group i {
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .form-group input:focus + i {
            color: var(--primary-color);
        }

        .form-group .toggle-password {
            right: 18px;
            left: auto;
            cursor: pointer;
            color: #64748b;
            transition: color 0.3s ease;
        }

        .form-group .toggle-password:hover {
            color: var(--primary-color);
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 100, 121, 0.2);
        }

        button:active {
            transform: translateY(0);
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

        form {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        @media screen and (max-width: 768px) {
            .container {
                flex-direction: column;
                margin: 10px;
                max-height: none;
            }

            .left-panel {
                width: 100%;
                padding: 30px 20px;
            }

            .right-panel {
                width: 100%;
                padding: 30px 20px;
            }

            form {
                max-width: 100%;
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
        <h2>Create Account</h2>
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error) echo "<p>$error</p>"; ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Your Full Name" required>
            </div>
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="bottom-text">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

<script>
    function togglePassword(inputId) {
        const pwd = document.getElementById(inputId);
        const eye = pwd.nextElementSibling;
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
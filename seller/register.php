<?php
include '../includes/db_connect.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $stall_name = trim($_POST['stall_name']);
    $terms_accepted = isset($_POST['terms']) ? true : false;

    $stall_image = '';
    if (isset($_FILES['stall_image']) && $_FILES['stall_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($_FILES['stall_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Please upload JPG, PNG, or GIF images only.";
        } elseif ($_FILES['stall_image']['size'] > $max_size) {
            $errors[] = "File size too large. Maximum size is 5MB.";
        } else {
            $upload_dir = 'uploads/stall_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['stall_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('stall_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['stall_image']['tmp_name'], $upload_path)) {
                $stall_image = $upload_path;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($stall_name)) {
        $errors[] = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } else {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } else {
            $domain = substr(strrchr($email, "@"), 1);
            $disposable_domains = ['tempmail.com', 'throwawaymail.com', 'mailinator.com', 'guerrillamail.com', '10minutemail.com'];
            if (in_array(strtolower($domain), $disposable_domains)) {
                $errors[] = "Disposable email addresses are not allowed.";
            }

            $common_domains = [
                'gmail.com' => ['gmial.com', 'gamil.com', 'gnail.com'],
                'yahoo.com' => ['yaho.com', 'yahooo.com'],
                'hotmail.com' => ['hotmal.com', 'hotmial.com'],
                'outlook.com' => ['outlok.com', 'outloo.com']
            ];

            foreach ($common_domains as $correct => $typos) {
                if (in_array($domain, $typos)) {
                    $errors[] = "Did you mean " . substr($email, 0, strpos($email, '@')) . '@' . $correct . "?";
                    break;
                }
            }

            $stmt = $conn->prepare("SELECT id FROM seller WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "This email address is already registered.";
            }
        }

        $password_errors = [];
        
        if (strlen($password) < 8) {
            $password_errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $password_errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $password_errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $password_errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $password_errors[] = "Password must contain at least one special character (!@#$%^&*()-_=+{};:,<.>)";
        }

        if (!empty($password_errors)) {
            $errors[] = "Password requirements not met:<br>" . implode("<br>", $password_errors);
        }
    }

    if (!$terms_accepted) {
        $errors[] = "You must accept the terms and conditions to register.";
    }

    if (!empty($errors)) {
    } else {
        $conn->begin_transaction();
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO seller (name, email, password, stall_name, role, stall_image) 
                    VALUES (?, ?, ?, ?, 'seller', ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $name, $email, $hashedPassword, $stall_name, $stall_image);
            $stmt->execute();

            $conn->commit();
            header("Location: login.php?registered=true");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed. Please try again.";
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
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
            outline: none;
        }

        .form-group i {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: var(--primary-color);
            z-index: 1;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(24, 100, 121, 0.2);
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

        .terms-group {
            margin: 15px 0;
        }

        .terms-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-color);
        }

        .terms-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .terms-label a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .terms-label a:hover {
            text-decoration: underline;
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

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
        }

        .file-upload-label i {
            color: var(--primary-color);
            font-size: 20px;
        }

        .file-upload-input {
            display: none;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            max-height: 200px;
            overflow: hidden;
            border-radius: 8px;
            display: none;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .email-requirements {
            margin-top: 8px;
            padding: 10px;
            background-color: var(--primary-light);
            border-radius: 6px;
            font-size: 12px;
            position: relative;
            z-index: 0;
        }

        .email-requirements p {
            margin: 0 0 5px 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .email-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: var(--text-color);
        }

        .email-requirements li {
            margin: 2px 0;
        }

        .password-requirements {
            margin-top: 8px;
            padding: 10px;
            background-color: var(--primary-light);
            border-radius: 6px;
            font-size: 12px;
            position: relative;
            z-index: 0;
        }

        .password-requirements p {
            margin: 0 0 5px 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: var(--text-color);
        }

        .password-requirements li {
            margin: 2px 0;
        }

        .form-group-with-requirements {
            margin-bottom: 25px;
            position: relative;
            width: 100%;
        }

        .form-group-with-requirements input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 35px 12px 40px;
        }

        .info-icon {
            position: absolute;
            right: 2px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            cursor: pointer;
            z-index: 2;
            font-size: 16px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            pointer-events: auto;
        }

        .info-icon:hover {
            color: var(--primary-hover);
            background: var(--primary-light);
        }

        .requirements-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            width: 100%;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .requirements-dropdown.show {
            display: block;
        }

        .requirements-dropdown p {
            margin: 0 0 5px 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .requirements-dropdown ul {
            margin: 0;
            padding-left: 20px;
            color: var(--text-color);
        }

        .requirements-dropdown li {
            margin: 2px 0;
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 90%;
            max-width: 700px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            color: #666;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: var(--primary-color);
        }

        .terms-content {
            margin-top: 20px;
            padding: 0 10px;
        }

        .terms-content h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: left;
        }

        .terms-content h3 {
            color: var(--primary-color);
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .terms-content p {
            margin: 10px 0;
            line-height: 1.6;
            color: var(--text-color);
        }

        @media screen and (max-width: 768px) {
            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 20px;
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
        <h2>Register Your Stall</h2>
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error) echo "<p>$error</p>"; ?>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Your Full Name" required>
            </div>
            <div class="form-group form-group-with-requirements">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required 
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       title="Please enter a valid email address">
                <i class="fas fa-info-circle info-icon" onclick="toggleRequirements('email-requirements')"></i>
                <div id="email-requirements" class="requirements-dropdown">
                    <p>Email requirements:</p>
                    <ul>
                        <li>Must be a valid email format</li>
                        <li>No disposable email addresses</li>
                        <li>Must be unique (not already registered)</li>
                    </ul>
                </div>
            </div>
            <div class="form-group form-group-with-requirements">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-info-circle info-icon" onclick="toggleRequirements('password-requirements')"></i>
                <div id="password-requirements" class="requirements-dropdown">
                    <p>Password must contain:</p>
                    <ul>
                        <li>At least 8 characters</li>
                        <li>One uppercase letter</li>
                        <li>One lowercase letter</li>
                        <li>One number</li>
                        <li>One special character (!@#$%^&*()-_=+{};:,<.>)</li>
                    </ul>
                </div>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <div class="form-group">
                <i class="fas fa-store"></i>
                <input type="text" name="stall_name" placeholder="Stall Name" required>
            </div>
            <div class="form-group">
                <label class="file-upload-label">
                    <i class="fas fa-image"></i>
                    <span>Upload Stall Logo</span>
                    <input type="file" name="stall_image" accept="image/*" class="file-upload-input">
                </label>
                <div id="image-preview" class="image-preview"></div>
            </div>
            <div class="form-group terms-group">
                <label class="terms-label">
                    <input type="checkbox" name="terms" required>
                    I agree to the <a href="#" onclick="showTerms()">Terms and Conditions</a>
                </label>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="bottom-text">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

<div id="termsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Terms and Conditions</h2>
        <div class="terms-content">
            <h3>1. Account Registration</h3>
            <p>By registering an account, you agree to provide accurate and complete information about yourself and your stall.</p>
            
            <h3>2. Stall Operation</h3>
            <p>You agree to maintain the quality of your products and services as advertised. Any false advertising or misrepresentation may result in account suspension.</p>
            
            <h3>3. Food Safety</h3>
            <p>You are responsible for ensuring all food items meet health and safety standards. Regular inspections may be conducted.</p>
            
            <h3>4. Payment Terms</h3>
            <p>Payments will be processed according to the platform's payment schedule. You agree to maintain accurate financial records.</p>
            
            <h3>5. Account Security</h3>
            <p>You are responsible for maintaining the security of your account credentials and any activities that occur under your account.</p>
        </div>
    </div>
</div>

<script>
function showTerms() {
    const modal = document.getElementById('termsModal');
    modal.style.display = "block";
}

document.querySelector('.close').onclick = function() {
    document.getElementById('termsModal').style.display = "none";
}

window.onclick = function(event) {
    const modal = document.getElementById('termsModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

document.querySelector('.file-upload-input').addEventListener('change', function(e) {
    const preview = document.getElementById('image-preview');
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Stall Logo Preview">`;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        preview.innerHTML = '';
    }
});

function toggleRequirements(id) {
    const dropdown = document.getElementById(id);
    dropdown.classList.toggle('show');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const dropdowns = document.getElementsByClassName('requirements-dropdown');
    for (let dropdown of dropdowns) {
        if (!event.target.closest('.form-group-with-requirements')) {
            dropdown.classList.remove('show');
        }
    }
});
</script>

</body>
</html>

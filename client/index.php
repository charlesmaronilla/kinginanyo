<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZ-ORDER - Welcome</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #186479;
            --primary-hover: #134d5d;
            --primary-light: rgba(24, 100, 121, 0.1);
            --text-color: #2d3748;
            --border-color: #e2e8f0;
            --background-light: #f5f7fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--background-light) 0%, #ffffff 100%);
            padding: 20px;
        }

        .container {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary-color);
        }

        .logo {
            width: 140px;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        h1 {
            font-size: 32px;
            margin-bottom: 12px;
            color: var(--primary-color);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        p {
            font-size: 16px;
            color: var(--text-color);
            margin-bottom: 35px;
            font-weight: 400;
            opacity: 0.9;
        }

        .button {
            display: block;
            width: 100%;
            padding: 16px;
            margin-bottom: 15px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .button:hover::after {
            transform: translateX(0);
        }

        .button.primary {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            box-shadow: 0 4px 15px rgba(24, 100, 121, 0.2);
        }

        .button.primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24, 100, 121, 0.3);
        }

        .button.secondary {
            background-color: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            box-shadow: 0 4px 15px rgba(24, 100, 121, 0.1);
        }

        .button.secondary:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24, 100, 121, 0.2);
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 28px;
            }

            .logo {
                width: 120px;
            }

            .button {
                padding: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="assets/logo.png" alt="EZ-ORDER Logo" class="logo">
        <h1>EZ-ORDER</h1>
        <p>Order. Grab. Eat.</p>
        <a href="login.php" class="button primary">SIGN IN</a>
        <a href="register.php" class="button secondary">SIGN UP</a>
    </div>
</body>
</html> 
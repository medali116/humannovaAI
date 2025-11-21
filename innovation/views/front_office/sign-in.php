<?php
// Sign-In Page - Uses MVC Architecture
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin'] == 1) {
        header('Location: ../back_office/dashboard.php');
    } else {
        header('Location: userprofile.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Pro Manage AI</title>
    <link rel="stylesheet" href="assets/templatemo-prism-flux.css">
    <style>
        /* Additional styles for sign-in page */
        .signin-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 60px;
            position: relative;
        }

        .signin-box {
            background: linear-gradient(135deg, var(--carbon-medium), var(--carbon-dark));
            border: 1px solid var(--metal-dark);
            border-radius: 20px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .signin-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-cyan));
        }

        .signin-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .signin-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .signin-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 10px;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            background: var(--carbon-dark);
            border: 1px solid var(--metal-dark);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Orbitron', 'Rajdhani', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 20px rgba(153, 69, 255, 0.3);
        }

        .form-group input::placeholder {
            color: var(--text-dim);
        }

        .signin-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: var(--text-primary);
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 10px;
        }

        .signin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(153, 69, 255, 0.5);
        }

        .signin-btn:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--metal-dark);
        }

        .form-footer a {
            color: var(--accent-cyan);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--accent-purple);
        }

        .form-footer p {
            color: var(--text-secondary);
            margin-bottom: 15px;
        }

        .error-message {
            background: rgba(255, 51, 51, 0.1);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
        }

        .success-message {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .password-toggle-btn:hover {
            color: var(--accent-cyan);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .remember-me input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .forgot-password {
            color: var(--accent-cyan);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--accent-purple);
        }

        @media (max-width: 768px) {
            .signin-box {
                padding: 30px 25px;
            }

            .signin-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header class="header" id="header">
        <nav class="nav-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <div class="logo-prism">
                        <div class="prism-shape"></div>
                    </div>
                </div>
                <span class="logo-text">
                    <span class="prism">Pro Manage</span>
                    <span class="flux">AI</span>
                </span>
            </a>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="innovation.php" class="nav-link">Innovation</a></li>
                <li><a href="#evenement" class="nav-link">Event</a></li>
                <li><a href="#reclamation" class="nav-link">Reclamation</a></li>
                <li><a href="#actualite" class="nav-link">News</a></li>
                <li><a href="sign-up.php" class="nav-link btn-signup">Sign Up</a></li>
                <li><a href="sign-in.php" class="nav-link btn-signin active">Sign In</a></li>
                <li class="profile-dropdown">
                    <a href="#" class="nav-link profile-icon" id="profileDropdownBtn" title="Profile">👤</a>
                    <div class="dropdown-menu" id="profileDropdownMenu">
                        <a href="userprofile.php" class="dropdown-item">
                            <span>👤</span> My Profile
                        </a>
                        <a href="logout.php" class="dropdown-item">
                            <span>🚪</span> Deconnexion
                        </a>
                    </div>
                </li>
            </ul>
            
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Sign In Container -->
    <div class="signin-container">
        <div class="signin-box">
            <div class="signin-header">
                <h1>🔐 Sign In</h1>
                <p>Welcome back! Enter your credentials to continue</p>
            </div>

            <!-- Error and success messages will be displayed here dynamically -->

            <form method="POST" action="" id="signInForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="text" 
                        id="email" 
                        name="email" 
                        placeholder="your.email@example.com"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-toggle">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                        >
                        <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                            👁️
                        </button>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="signin-btn">
                    Sign In
                </button>
            </form>

            <div class="form-footer">
                <p>Don't have an account yet?</p>
                <a href="sign-up.php">Create Account →</a>
            </div>
        </div>
    </div>

    <script src="assets/js/templatemo-prism-scripts.js"></script>
    <script src="assets/js/signin-validation.js"></script>
    <script>
        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileDropdownBtn');
            const dropdownMenu = document.getElementById('profileDropdownMenu');
            
            if (profileBtn && dropdownMenu) {
                profileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdownMenu.classList.toggle('show');
                });
                
                document.addEventListener('click', function(e) {
                    if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }
        });
    </script>
    <style>
        .profile-dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; top: 100%; right: 0;
            background: var(--carbon-dark); border: 1px solid var(--metal-dark);
            border-radius: 10px; padding: 10px 0; min-width: 180px;
            margin-top: 10px; opacity: 0; visibility: hidden;
            transform: translateY(-10px); transition: all 0.3s ease;
            z-index: 1000; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .dropdown-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 20px; color: var(--text-primary);
            text-decoration: none; transition: all 0.3s ease;
        }
        .dropdown-item:hover { background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan)); }
        .dropdown-item span { font-size: 1.2rem; }
    </style>
</body>
</html>

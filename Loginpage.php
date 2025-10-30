<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "student_dashboard";
$db_username = "root";
$db_password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Default passwords for auto-creation
$default_passwords = [
    "student" => "student123",
    "faculty" => "faculty123",
    "security" => "security123"
];

// Get form data and errors from session (for display after redirect)
$submitted_userType = $_SESSION['submitted_userType'] ?? '';
$submitted_username = $_SESSION['submitted_username'] ?? '';
$userTypeError = $_SESSION['userTypeError'] ?? '';
$usernameError = $_SESSION['usernameError'] ?? '';
$passwordError = $_SESSION['passwordError'] ?? '';

// Clear session data after retrieving
unset($_SESSION['submitted_userType']);
unset($_SESSION['submitted_username']);
unset($_SESSION['userTypeError']);
unset($_SESSION['usernameError']);
unset($_SESSION['passwordError']);

// Handle form submission
if(isset($_POST['loginBtn'])) {
    $userType = trim($_POST['userType'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rollpattern = '/^2[2-5]p61a(01|02|03|04|05|12)[a-zA-Z0-9]{2}$/';
    
    // Store form data in session for redisplay
    $_SESSION['submitted_userType'] = $userType;
    $_SESSION['submitted_username'] = $username;
    
    // Validate inputs
    $hasErrors = false;
    
    if($userType === "") {
        $_SESSION['userTypeError'] = "Please select user type!";
        $hasErrors = true;
    }

    if($username === "") {
        $_SESSION['usernameError'] = "Please enter username!";
        $hasErrors = true;
    } elseif($userType === "student" && !preg_match($rollpattern, $username)) {
        $_SESSION['usernameError'] = "Invalid roll number!";
        $hasErrors = true;
    }

    if($password === "") {
        $_SESSION['passwordError'] = "Please enter password!";
        $hasErrors = true;
    }

    // If validation errors, redirect back
    if($hasErrors) {
        header("Location: Loginpage.php");
        exit();
    }

    // If no validation errors, proceed with authentication
    if($userType === "student") {
        try {
            // Check if student exists in database
            $stmt = $pdo->prepare("SELECT * FROM students WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user) {
                // Student exists - check password from database
                if($password === $user['password']) {
                    $_SESSION['user_type'] = 'student';
                    $_SESSION['username'] = $username;
                    $_SESSION['loggedin'] = true;
                    header("Location: student_dashboard.php");
                    exit();
                } else {
                    $_SESSION['passwordError'] = "Invalid password!";
                    header("Location: Loginpage.php");
                    exit();
                }
            } else {
                // Student doesn't exist - check default password for auto-creation
                if($password === $default_passwords['student']) {
                    // Auto-create new student account
                    $insert_stmt = $pdo->prepare("INSERT INTO students (username, password) VALUES (:username, :password)");
                    $insert_stmt->execute([
                        'username' => $username, 
                        'password' => $default_passwords['student']
                    ]);
                    
                    // Login successful after auto-creation
                    $_SESSION['user_type'] = 'student';
                    $_SESSION['username'] = $username;
                    $_SESSION['loggedin'] = true;
                    header("Location: student_dashboard.php");
                    exit();
                } else {
                    $_SESSION['passwordError'] = "Invalid password! Use default: student123 for first login";
                    header("Location: Loginpage.php");
                    exit();
                }
            }
        } catch(PDOException $e) {
            $_SESSION['passwordError'] = "Database error: " . $e->getMessage();
            header("Location: Loginpage.php");
            exit();
        }
    } elseif($userType === "faculty" || $userType === "security") {
        $table = ($userType === "faculty") ? "faculty" : "security";
        $default_password = $default_passwords[$userType];
        
        try {
            // Check if user exists in database
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user) {
                // User exists - check password from database
                if($password === $user['password']) {
                    $_SESSION['user_type'] = $userType;
                    $_SESSION['username'] = $username;
                    $_SESSION['loggedin'] = true;
                    
                    if($userType === "faculty") {
                        header("Location: faculty_dashboard.php");
                    } else {
                        header("Location: security_dashboard.php");
                    }
                    exit();
                } else {
                    $_SESSION['passwordError'] = "Invalid password!";
                    header("Location: Loginpage.php");
                    exit();
                }
            } else {
                // User doesn't exist - check default password for auto-creation
                if($password === $default_password) {
                    // Auto-create new account
                    $insert_stmt = $pdo->prepare("INSERT INTO $table (username, password) VALUES (:username, :password)");
                    $insert_stmt->execute([
                        'username' => $username, 
                        'password' => $default_password
                    ]);
                    
                    // Login successful after auto-creation
                    $_SESSION['user_type'] = $userType;
                    $_SESSION['username'] = $username;
                    $_SESSION['loggedin'] = true;
                    
                    if($userType === "faculty") {
                        header("Location: faculty_dashboard.php");
                    } else {
                        header("Location: security_dashboard.php");
                    }
                    exit();
                } else {
                    $_SESSION['passwordError'] = "Invalid password!";
                    header("Location: Loginpage.php");
                    exit();
                }
            }
        } catch(PDOException $e) {
            $_SESSION['passwordError'] = "Database error: " . $e->getMessage();
            header("Location: Loginpage.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Digital Outpass System - Login</title>
    <style>
       body {
           margin: 0;
           font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
           display: flex;
           flex-direction: column;
           justify-content: center;
           align-items: center;
           min-height: 100vh;
           background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
           padding: 20px;
       }

       .header {
           text-align: center;
           margin-bottom: 30px;
       }

       .header h1 {
           color: #27ae60;
           font-size: 32px;
           margin-bottom: 10px;
           font-weight: 700;
           text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
       }

       .header p {
           color: #546e7a;
           font-size: 16px;
           margin: 0;
       }

       .login-container {
           display: flex;
           flex-direction: column;
           align-items: center;
           width: 100%;
           max-width: 420px;
       }

       .login-box {
           background: #ffffff;
           padding: 40px 30px;
           border-radius: 12px;
           box-shadow: 0 10px 25px rgba(0, 105, 108, 0.15);
           width: 100%;
           text-align: center;
           border-top: 4px solid #4a90e2;
       }

       .login-box h2 {
           margin-bottom: 25px;
           color: #2c3e50;
           font-size: 24px;
           font-weight: 600;
       }

       .login-box label {
           display: block;
           text-align: left;
           font-weight: 600;
           margin-bottom: 5px;
           color: #555;
           font-size: 14px;
       }

       .login-box input,
       .login-box select {
           width: 100%;
           padding: 12px 12px;
           margin-bottom: 12px;
           border: 1px solid #ddd;
           border-radius: 8px;
           font-size: 14px;
           box-sizing: border-box;
           transition: all 0.3s;
       }

       .login-box input:focus,
       .login-box select:focus {
           border-color: #4a90e2;
           outline: none;
           box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
       }

       .login-box button {
           width: 100%;
           padding: 12px;
           background-color: #4a90e2;
           color: white;
           border: none;
           border-radius: 8px;
           font-size: 16px;
           font-weight: 600;
           cursor: pointer;
           transition: 0.3s;
           margin-top: 10px;
       }

       .login-box button:hover {
           background-color: #357abd;
           transform: translateY(-2px);
           box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
       }

       .error {
           color: #e53935;
           font-size: 12px;
           height: 14px;
           margin-bottom: 5px;
           text-align: left;
           font-weight: 500;
       }

       .success {
           color: #43a047;
           font-size: 14px;
           margin-bottom: 10px;
       }

       .footer {
           margin-top: 30px;
           text-align: center;
           color: #78909c;
           font-size: 14px;
       }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <h1>Digital Outpass System</h1>
            <p>Secure Campus Access Management</p>
        </div>

        <div class="login-box">
            <h2>Login to Your Account</h2>

            <form method="POST" action="Loginpage.php">
                <label>Select User Type</label>
                <select name="userType">
                    <option value="">--Select--</option>
                    <option value="student" <?php echo ($submitted_userType == 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="faculty" <?php echo ($submitted_userType == 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                    <option value="security" <?php echo ($submitted_userType == 'security') ? 'selected' : ''; ?>>Security</option>
                </select>
                <div class="error"><?php echo $userTypeError; ?></div>

                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($submitted_username); ?>" placeholder="Enter username">
                <div class="error"><?php echo $usernameError; ?></div>

                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password">
                <div class="error"><?php echo $passwordError; ?></div>

                <button type="submit" name="loginBtn">Login</button>
            </form>
        </div>

        <div class="footer">
            &copy; <?php echo date("Y"); ?> Digital Outpass System. All rights reserved.
        </div>
    </div>
</body>
</html>
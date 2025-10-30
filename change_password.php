<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['loggedin'])) {
    header("Location: Loginpage.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_dashboard";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Check for success message from redirect
if(isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if(isset($_POST['changePasswordBtn'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $username = $_SESSION['username'];
    $user_type = $_SESSION['user_type'];
    
    // Determine table based on user type
    $table = '';
    switch($user_type) {
        case 'student':
            $table = 'students';
            break;
        case 'faculty':
            $table = 'faculty';
            break;
        case 'security':
            $table = 'security';
            break;
        default:
            $message = "Invalid user type: " . $user_type;
            break;
    }
    
    if(empty($table)) {
        $message = "Error: Could not determine database table for user type '" . $user_type . "'";
    } else {
        // Check if current password is correct - FIXED CODE
        $check = "SELECT password FROM $table WHERE username='$username'";
        $result = $conn->query($check);
        
        if($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if($user['password'] === $current_password) {
                if($new_password === $confirm_password) {
                    // Update password in database
                    $update = "UPDATE $table SET password='$new_password' WHERE username='$username'";
                    if($conn->query($update) === TRUE) {
                        $_SESSION['success_message'] = "Password changed successfully!";
                        header("Location: change_password.php"); // Redirect to avoid resubmission
                        exit();
                    } else {
                        $message = "Error updating password: " . $conn->error;
                    }
                } else {
                    $message = "New passwords don't match!";
                }
            } else {
                $message = "Current password is incorrect!";
            }
        } else {
            $message = "User not found!";
        }
    }
}

// Determine dashboard redirect based on user type
$dashboard_page = $_SESSION['user_type'] . '_dashboard.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8f9fa; /* Light background color */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        .password-box { 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            width: 450px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-top: 5px solid #4a90e2; /* Blue border */
        }
        h2 {
            color: #27ae60; /* Green color for heading */
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }
        .user-type-badge {
            background: #4a90e2; /* Blue background */
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
            text-transform: capitalize;
        }
        input { 
            width: 100%; 
            padding: 15px; 
            margin: 10px 0; 
            border: 2px solid #e9ecef; 
            border-radius: 8px; 
            font-size: 16px;
            transition: all 0.3s ease;
        }
        input:focus {
            border-color: #4a90e2; /* Blue focus border */
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        button { 
            width: 100%; 
            padding: 15px; 
            background: #4a90e2; /* Blue button */
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        button:hover { 
            background: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
        }
        .message { 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 8px; 
            text-align: center; 
            font-weight: bold;
        }
        .success { 
            background: #d4f8e8;
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .error { 
            background: #fdeaea;
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #4a90e2; /* Blue color */
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
            border: 2px solid #4a90e2; /* Blue border */
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .back-link a:hover {
            background: #4a90e2;
            color: white;
        }
    </style>
</head>
<body>
    <div class="password-box">
        <h2>Change Password</h2>
        <div style="text-align: center;">
            <span class="user-type-badge"><?php echo ucfirst($_SESSION['user_type']); ?></span>
        </div>
        
        <?php if($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="password" name="current_password" placeholder="Current Password" required>
            
            <input type="password" name="new_password" placeholder="New Password" required>
            
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            
            <button type="submit" name="changePasswordBtn">Change Password</button>
        </form>
        
        <div class="back-link">
            <a href="<?php echo $dashboard_page; ?>">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
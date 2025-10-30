<?php
session_start();

// Check if security is logged in
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'security'){
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

// Handle mark as returned - WITH REDIRECT
if(isset($_POST['mark_returned'])){
    $roll_number = $_POST['roll_number'];
    
    // Check if student has pending outpass today
    $check_sql = "SELECT * FROM outpass WHERE student_id = '$roll_number' AND status = 'pending' AND today_date = CURDATE()";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows > 0) {
        // Mark as returned
        $update_sql = "UPDATE outpass SET status = 'returned' WHERE student_id = '$roll_number' AND status = 'pending' AND today_date = CURDATE()";
        if($conn->query($update_sql) === TRUE) {
            $_SESSION['success_message'] = "✅ Student $roll_number marked as returned!";
        } else {
            $_SESSION['error_message'] = "❌ Error updating record: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "❌ No pending outpass found for $roll_number today";
    }
    
    // REDIRECT to avoid form resubmission
    header("Location: security_dashboard.php");
    exit();
}

// Check for messages from redirect
if(isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} elseif(isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Get today's pending outpasses
$pending_sql = "SELECT * FROM outpass WHERE status = 'pending' AND today_date = CURDATE() ORDER BY leave_time DESC";
$pending_result = $conn->query($pending_sql);

// Get statistics
$total_pending = $pending_result->num_rows;
$total_returned = $conn->query("SELECT COUNT(*) as returned FROM outpass WHERE status = 'returned' AND today_date = CURDATE()")->fetch_assoc()['returned'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Security Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8f9fa; /* Light background color */
            min-height: 100vh;
            color: #333;
        }
        
        .header { 
            background: #2c3e50; 
            color: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        
        .header div:first-child {
            font-size: 20px;
            font-weight: 600;
        }
        
        .header-links {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header a { 
            color: white; 
            text-decoration: none; 
            padding: 8px 16px;
            border: 1px solid white;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 14px;
        }
        
        .header a:hover { 
            background: white;
            color: #2c3e50;
        }
        
        .container { 
            padding: 30px; 
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 5px solid #4a90e2; /* Blue border only */
        }
        
        .stat-card.pending {
            border-top-color: #4a90e2; /* Same blue border */
        }
        
        .stat-card.returned {
            border-top-color: #4a90e2; /* Same blue border */
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50; /* Black color for numbers */
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .verification-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border-top: 5px solid #4a90e2; /* Blue border only */
        }
        
        .verification-section h2 {
            color: #2c3e50; /* Black color for heading */
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4a90e2; /* Blue focus border */
        }
        
        .btn {
            padding: 12px 30px;
            background: #4a90e2; /* Blue button */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #357abd;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #4a90e2; /* Same blue button */
        }
        
        .btn-success:hover {
            background: #357abd;
        }
        
        .pending-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            border-top: 5px solid #4a90e2; /* Blue border only */
        }
        
        .pending-list h2 {
            background: #4a90e2; /* Blue background for heading */
            color: white;
            padding: 20px;
            margin: 0;
            font-size: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #4a90e2; /* Blue background for table header */
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .student-id {
            font-family: monospace;
            font-weight: 600;
            color: #2c3e50; /* Black color for roll numbers */
        }
        
        .message { 
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }
        
        .success {
            background: #d4f8e8;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-section h1 {
            color: #27ae60; /* Green color for main heading */
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .welcome-section p {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .user-welcome {
            margin-right: 15px;
            font-size: 14px;
            color: #ecf0f1;
        }
    </style>
</head>
<body>

<div class="header">
    <div>Digital Outpass System - Security Panel</div>
    <div class="header-links">
        <span class="user-welcome">Welcome, Security</span>
        <a href="change_password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="welcome-section">
        <h1>Security Dashboard</h1>
        <p>Verify student outpasses and mark returns</p>
    </div>

    <?php if($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card pending">
            <div class="stat-number"><?php echo $total_pending; ?></div>
            <div class="stat-label">Students Out</div>
        </div>
        <div class="stat-card returned">
            <div class="stat-number"><?php echo $total_returned; ?></div>
            <div class="stat-label">Students Returned</div>
        </div>
    </div>

    <!-- Quick Verification -->
    <div class="verification-section">
        <h2>🔍 Quick Student Verification</h2>
        <form method="post" class="search-form">
            <div class="form-group">
                <label for="roll_number">Enter Student Roll Number:</label>
                <input type="text" id="roll_number" name="roll_number" required>
            </div>
            <button type="submit" name="mark_returned" class="btn btn-success">Mark as Returned</button>
        </form>
    </div>

    <!-- Pending Outpasses List -->
    <div class="pending-list">
        <h2>📋 Today's Pending Outpasses</h2>
        <?php if($pending_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Leave Time</th>
                        <th>Expected Return</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $pending_result->fetch_assoc()): ?>
                    <tr>
                        <td class="student-id"><?php echo $row['student_id']; ?></td>
                        <td><?php echo date('h:i A', strtotime($row['leave_time'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($row['return_time'])); ?></td>
                        <td>
                            <span style="color: #e74c3c; font-weight: 600;">● OUT</span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                No pending outpasses for today. All students are in college.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
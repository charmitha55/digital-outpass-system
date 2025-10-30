<?php
session_start();

// Check if student is logged in
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'student'){
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

// Get student username
$student_id = $_SESSION['username'];
$current_year = date('Y');

// Count how many outpasses student has used this year
$count_sql = "SELECT COUNT(*) as used_count FROM outpass 
              WHERE student_id='$student_id' 
              AND YEAR(today_date) = '$current_year'";
$count_result = $conn->query($count_sql);
$used_count = $count_result->fetch_assoc()['used_count'];
$remaining_outpasses = 3 - $used_count;

// Get actual outpass IDs used by this student
$outpass_ids_sql = "SELECT id FROM outpass 
                    WHERE student_id='$student_id' 
                    AND YEAR(today_date) = '$current_year' 
                    ORDER BY id";
$outpass_ids_result = $conn->query($outpass_ids_sql);
$used_outpass_ids = [];
while($row = $outpass_ids_result->fetch_assoc()) {
    $used_outpass_ids[] = $row['id'];
}

$message = "";

// Check for success message from redirect
if(isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Handle form submission - WITH REDIRECT
if(isset($_POST['submit'])){
    $reason = $conn->real_escape_string($_POST['reason']);
    $leave_time = $_POST['leave_time'];
    $return_time = $_POST['return_time'];
    $today_date = date('Y-m-d');

    // Check if student has remaining outpasses
    if($remaining_outpasses <= 0) {
        $_SESSION['error_message'] = "You have used all your 3 outpasses for this year!";
    } 
    // Check if student already has an outpass today
    else {
        $check = "SELECT * FROM outpass WHERE student_id='$student_id' AND today_date='$today_date'";
        $result = $conn->query($check);

        if($result->num_rows > 0){
            $_SESSION['error_message'] = "You have already submitted an outpass for today!";
        } else {
            $sql = "INSERT INTO outpass (student_id, reason, today_date, leave_time, return_time) 
                    VALUES ('$student_id', '$reason', '$today_date', '$leave_time', '$return_time')";
            if($conn->query($sql) === TRUE) {
                // Get the new outpass ID
                $new_outpass_id = $conn->insert_id;
                
                // Store outpass ID in session for confirmation page
                $_SESSION['last_outpass_id'] = $new_outpass_id;
                $_SESSION['success_message'] = "🎉 Outpass submitted successfully! 
                           <br><br>
                           <a href='outpass_confirmation.php' 
                              style='display: inline-block; 
                                     background: #3498db; 
                                     color: white; 
                                     padding: 12px 25px; 
                                     border-radius: 8px; 
                                     text-decoration: none; 
                                     font-weight: bold;
                                     margin-top: 10px;'>
                           📋 View Outpass Confirmation
                           </a>";
                
                // UPDATE THE COUNT IMMEDIATELY AFTER SUCCESS
                $used_count = $used_count + 1;
                $remaining_outpasses = 3 - $used_count;
                
                // Refresh the used_outpass_ids
                $outpass_ids_sql = "SELECT id FROM outpass 
                                    WHERE student_id='$student_id' 
                                    AND YEAR(today_date) = '$current_year' 
                                    ORDER BY id";
                $outpass_ids_result = $conn->query($outpass_ids_sql);
                $used_outpass_ids = [];
                while($row = $outpass_ids_result->fetch_assoc()) {
                    $used_outpass_ids[] = $row['id'];
                }
                
            } else {
                $_SESSION['error_message'] = "Error: " . $conn->error;
            }
        }
    }
    
    // REDIRECT to avoid form resubmission
    header("Location: student_dashboard.php");
    exit();
}

// Check for error message after redirect
if(isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        
        .dashboard-card {
            background: linear-gradient(135deg, #4a90e2, #357abd); /* Same blue color for all boxes */
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: none;
            color: white;
        }
        
        .outpass-counter {
            background: linear-gradient(135deg, #4a90e2, #357abd); /* Same blue color for all boxes */
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: none;
        }
        
        .counter-number {
            font-size: 32px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .counter-text {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .counter-subtext {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 8px;
        }
        
        .pass-badges {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .pass-badge {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.2);
        }
        
        .pass-used {
            background: #e74c3c;
            color: white;
            border-color: #c0392b;
        }
        
        .pass-available {
            background: #27ae60;
            color: white;
            border-color: #229954;
        }
        
        h2 { 
            color: white;
            margin-bottom: 15px;
            font-size: 22px;
            font-weight: 600;
        }
        
        .date-display {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        form { 
            background: rgba(255,255,255,0.95);
            padding: 25px;
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        label { 
            display: block;
            margin: 12px 0 6px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }
        
        input, textarea { 
            width: 100%; 
            padding: 12px; 
            margin: 6px 0 15px; 
            border-radius: 8px; 
            border: 1px solid #dcdfe4;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #fafbfc;
        }
        
        input:focus, textarea:focus {
            border-color: #4a90e2;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        textarea {
            height: 100px;
            resize: vertical;
        }
        
        input[type="submit"] { 
            background: #4a90e2; /* Same blue color for submit button */
            color: white; 
            border: none; 
            cursor: pointer; 
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        input[type="submit"]:hover { 
            background: #357abd;
        }
        
        input[type="submit"]:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        .message { 
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
            font-size: 14px;
        }
        
        .success {
            background: #d4f8e8;
            color: #27ae60;
            border: 1px solid #a3e4c1;
        }
        
        .error {
            background: #fdeaea;
            color: #e74c3c;
            border: 1px solid #f5b7b1;
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
        
        .no-outpass-message {
            color: #e74c3c;
            text-align: center;
            margin-top: 12px;
            font-weight: 500;
            font-size: 14px;
            padding: 10px;
            background: #fdeaea;
            border-radius: 6px;
            border: 1px solid #f5b7b1;
        }
    </style>
</head>
<body>

<div class="header">
    <div>Digital Outpass System</div>
    <div class="header-links">
        <span class="user-welcome">Welcome, <?php echo $_SESSION['username']; ?></span>
        <a href="change_password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="welcome-section">
        <h1>Student Dashboard</h1>
        <p>Manage your outpass requests</p>
    </div>

    <!-- Outpass Counter -->
    <div class="outpass-counter">
        <div class="counter-text">Outpass Status - Year <?php echo $current_year; ?></div>
        <div class="counter-number"><?php echo $remaining_outpasses; ?> of 3 remaining</div>
        <div class="counter-subtext">
            Used: <?php echo $used_count; ?> passes | Remaining: <?php echo $remaining_outpasses; ?> passes
        </div>
        
        <!-- Pass Badges -->
        <div class="pass-badges">
            <?php 
            $pass_number = 1;
            foreach($used_outpass_ids as $outpass_id): ?>
                <div class="pass-badge pass-used">
                    Pass <?php echo $pass_number; ?>
                </div>
            <?php 
                $pass_number++;
            endforeach; 
            
            for($i = $pass_number; $i <= 3; $i++): ?>
                <div class="pass-badge pass-available">
                    Pass <?php echo $i; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="date-display">Today: <?php echo date('d-m-Y'); ?></div>
        
        <h2>Request Outpass</h2>
        
        <?php if($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <label>Reason for Leaving:</label>
            <textarea name="reason" placeholder="Enter your reason..." required></textarea>

            <label>Time of Leaving:</label>
            <input type="time" name="leave_time" required>

            <label>Expected Time of Return:</label>
            <input type="time" name="return_time" required>

            <input type="submit" name="submit" value="Submit Outpass Request" 
                   <?php if($remaining_outpasses <= 0) echo 'disabled'; ?>>
            
            <?php if($remaining_outpasses <= 0): ?>
                <div class="no-outpass-message">
                    All outpasses used for <?php echo $current_year; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
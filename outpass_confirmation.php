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

// Get the latest outpass for this student
$student_id = $_SESSION['username'];
$outpass_sql = "SELECT * FROM outpass WHERE student_id = '$student_id' ORDER BY id DESC LIMIT 1";
$outpass_result = $conn->query($outpass_sql);
$outpass = $outpass_result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Outpass Confirmation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .confirmation-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .success-icon {
            font-size: 80px;
            color: #27ae60;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .outpass-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            text-align: left;
            border-left: 5px solid #3498db;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .detail-value {
            color: #34495e;
            font-weight: 500;
        }
        
        .roll-number {
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .print-btn {
            background: #27ae60;
            color: white;
        }
        
        .print-btn:hover {
            background: #229954;
        }
        
        @media print {
            body {
                background: white !important;
            }
            .confirmation-card {
                box-shadow: none !important;
                margin: 0 !important;
            }
            .action-buttons {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="success-icon">✅</div>
        <h1>Outpass Approved!</h1>
        <p class="subtitle">Your outpass request has been submitted successfully</p>
        
        <div class="outpass-details">
            <div class="detail-row">
                <span class="detail-label">Roll Number:</span>
                <span class="detail-value roll-number"><?php echo $outpass['student_id']; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value"><?php echo date('d-m-Y', strtotime($outpass['today_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Leave Time:</span>
                <span class="detail-value"><?php echo date('h:i A', strtotime($outpass['leave_time'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Expected Return:</span>
                <span class="detail-value"><?php echo date('h:i A', strtotime($outpass['return_time'])); ?></span>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="btn print-btn" onclick="window.print()">🖨️ Print</button>
            <a href="student_dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
</body>
</html>
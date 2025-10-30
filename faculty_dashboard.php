<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'faculty'){
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

// Get filter
$status_filter = $_GET['status'] ?? 'today'; // Default to today's view

// Build query based on status filter
if($status_filter == 'returned') {
    $where_clause = "WHERE status = 'returned'";
} elseif($status_filter == 'pending') {
    $where_clause = "WHERE status = 'pending'";
} elseif($status_filter == 'all') {
    $where_clause = "";
} else {
    $where_clause = "WHERE today_date = CURDATE()"; // Default: today
}

// Get outpass statistics
$today_outpasses = $conn->query("SELECT COUNT(*) as today FROM outpass WHERE today_date = CURDATE()")->fetch_assoc()['today'];
$returned_outpasses = $conn->query("SELECT COUNT(*) as returned FROM outpass WHERE status = 'returned'")->fetch_assoc()['returned'];
$pending_outpasses = $conn->query("SELECT COUNT(*) as pending FROM outpass WHERE status = 'pending'")->fetch_assoc()['pending'];

// Get outpass data for the TABLE based on filter
$outpass_query = "SELECT * FROM outpass $where_clause ORDER BY today_date DESC, leave_time DESC";
$outpass_result = $conn->query($outpass_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Faculty Dashboard</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 5px solid #4a90e2; /* Blue border */
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.today {
            border-top-color: #4a90e2; /* Same blue border */
        }
        
        .stat-card.returned {
            border-top-color: #4a90e2; /* Same blue border */
        }
        
        .stat-card.pending {
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
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border-top: 5px solid #4a90e2; /* Blue border */
        }
        
        .filter-section label {
            font-weight: 600;
            color: #2c3e50;
            margin-right: 15px;
        }
        
        .filter-section select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .filter-section select:focus {
            outline: none;
            border-color: #4a90e2; /* Blue focus border */
        }
        
        .outpass-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            overflow-x: auto;
            border-top: 5px solid #4a90e2; /* Blue border */
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        th {
            background: #4a90e2; /* Blue background for table header */
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .student-id {
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50; /* Black color for roll numbers */
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-returned {
            background: #d4f8e8;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
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
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="header">
    <div>Digital Outpass System - Faculty Panel</div>
    <div class="header-links">
        <span class="user-welcome">Welcome, Faculty</span>
        <a href="change_password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="welcome-section">
        <h1>Faculty Dashboard</h1>
        <p>Monitor student outpass status</p>
    </div>

    <!-- Statistics Cards - Only important ones -->
    <div class="stats-cards">
        <div class="stat-card today" onclick="filterOutpasses('today')">
            <div class="stat-number"><?php echo $today_outpasses; ?></div>
            <div class="stat-label">Today's Outpasses</div>
        </div>
        <div class="stat-card returned" onclick="filterOutpasses('returned')">
            <div class="stat-number"><?php echo $returned_outpasses; ?></div>
            <div class="stat-label">Returned Students</div>
        </div>
        <div class="stat-card pending" onclick="filterOutpasses('pending')">
            <div class="stat-number"><?php echo $pending_outpasses; ?></div>
            <div class="stat-label">Pending Return</div>
        </div>
    </div>

    <!-- Filter Section - All options available here -->
    <div class="filter-section">
        <label for="statusFilter">Show:</label>
        <select id="statusFilter" onchange="filterOutpasses(this.value)">
            <option value="today" <?php echo $status_filter == 'today' ? 'selected' : ''; ?>>Today's Outpasses</option>
            <option value="returned" <?php echo $status_filter == 'returned' ? 'selected' : ''; ?>>Returned Students</option>
            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending Return</option>
            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Outpasses</option>
        </select>
    </div>

    <!-- Outpass Table -->
    <div class="outpass-table">
        <?php if($outpass_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Roll Number</th>
                        <th>Reason</th>
                        <th>Leave Time</th>
                        <th>Expected Return</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $outpass_result->fetch_assoc()): ?>
                    <tr>
                        <td><span class="student-id"><?php echo $row['student_id']; ?></span></td>
                        <td><?php echo $row['reason']; ?></td>
                        <td><?php echo date('h:i A', strtotime($row['leave_time'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($row['return_time'])); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['today_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                No outpass requests found for the selected filter.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterOutpasses(status) {
    window.location.href = 'faculty_dashboard.php?status=' + status;
}
</script>

</body>
</html>
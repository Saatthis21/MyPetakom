<?php
session_start();

if (isset($_SESSION['message']) && $_SESSION['message_type'] === 'success') {
    $message = $_SESSION['message'];
    echo "<script>alert('$message');</script>";
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Check if user is logged in as coordinator
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Coordinator') {
    header("Location: LoginPage.html");
    exit();
}

$loginID = $_SESSION['loginID'];

// Database connection
$host = 'localhost';
$dbname = 'login_system';
$usernameDB = 'root';
$passwordDB = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $usernameDB, $passwordDB);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch coordinator name
    $stmt = $conn->prepare("SELECT coordinator_name FROM coordinator WHERE loginID = ?");
    $stmt->execute([$loginID]);
    $coordinator = $stmt->fetch(PDO::FETCH_ASSOC);
    $cdName = $coordinator ? $coordinator['coordinator_name'] : 'coordinator';

    // Fetch event attendance data for chart - CORRECTED COLUMN NAMES
    $eventQuery = "
        SELECT 
            e.event_name,
            COUNT(a.AttendanceID) as attendee_count,
            e.event_date
        FROM event e 
        LEFT JOIN attendance a ON e.EventID = a.EventID 
        GROUP BY e.EventID, e.event_name, e.event_date
        ORDER BY e.event_date DESC
        LIMIT 10
    ";
    $eventStmt = $conn->prepare($eventQuery);
    $eventStmt->execute();
    $eventData = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch dashboard statistics
    $totalStudents = $conn->query("SELECT COUNT(*) as count FROM student")->fetch()['count'];
    $totalEvents = $conn->query("SELECT COUNT(*) as count FROM event")->fetch()['count'];
    $totalAttendance = $conn->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
    
    // Calculate active participants (students who attended at least one event) - CORRECTED COLUMN NAMES
    $activeParticipants = $conn->query("
        SELECT COUNT(DISTINCT s.studentID) as count 
        FROM student s 
        INNER JOIN attendance a ON s.studentID = a.studentID
    ")->fetch()['count'];

    // Pending membership approvals (assuming you have a membership table)
    $pendingMemberships = 5; 
    $pendingMerits = 3; 

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Prepare data for JavaScript
$eventNames = [];
$attendeeCounts = [];
foreach ($eventData as $event) {
    $eventNames[] = $event['event_name'];
    $attendeeCounts[] = (int)$event['attendee_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = "logout.php";
            }
        }
    </script>
    <meta charset="UTF-8">
    <title>Coordinator Dashboard</title>
    <style>
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
        font-family: Arial, sans-serif;
    }
    
    body { 
        background-color: #f5f5f5;
        display: flex;
        flex-direction: column;
        height: 100vh; /* Full height */
    }
    
    /* Header styles */
    .logo {
        background-color: #1976D2;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
        position: fixed; 
        width: 100%;
        top: 0; 
        left: 0; 
        z-index: 10; 
    }
    
    .logo img { 
        width: 100px; 
        height: 100px; 
    }
    
    .logo-text { 
        margin-left: 15px; 
    }
    
    /* Sidebar styles */
    .sidebar {
        width: 200px;
        background-color: #2196F3;
        height: 100%;
        position: fixed;
        top: 0; /* Align to the top */
        padding-top: 120px; 
        color: black;
        overflow-y: auto; 
        
    }
    
    .sidebar button {
        display: block;
        width: 100%;
        padding: 30px;
        border: none;
        background: none;
        font-size: 16px;
        cursor: pointer;
        color: black;
        text-align: center;
        transition: background-color 0.3s;
    }
    
    .sidebar button:hover,
    .sidebar .active {
        background-color: #0D47A1;
        color: black; 
        font-weight: bold; 
    }
    
    /* Main content area */
    .content {
        margin-left: 220px;
        padding-top: 20px; 
        padding: 20px;
        margin-top: 100px; 
        height: calc(100vh - 100px); 
        overflow-y: auto; 
    }
    
    .dashboard-header {
        font-size: 28px;
        margin-bottom: 30px;
        color: #333;
        font-weight: bold;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        height: calc(100vh - 200px);
    }
    
    .graph-container {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }
    .chart-title {
        font-size: 20px;
        font-weight: bold;
        color: #1976D2;
        margin-bottom: 15px;
        text-align: center;
    }
    .chart-container {
        flex: 1;
        position: relative;
        min-height: 400px;
    }
    
   
    .right-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    /* Pending approvals section */
    .approvals-section, .stats-section {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        flex: 1;
    }

    .section-title {
        color: #1976D2;
        margin-bottom: 15px;
        font-size: 18px;
    }
    
    .approval-item {
        margin-bottom: 15px;
    }
    
    .approval-count {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    
    .approval-label {
        color: #666;
        margin-top: 5px;
    }
    
   
    .stats-item {
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    
    .stats-label {
        color: #666;
    }
    
    .stats-value {
        font-weight: bold;
        color: #1976D2;
    }
    
    .participation-rate {
        font-size: 14px;
        color: #4CAF50;
        font-weight: bold;
    }
</style>
</head>
<body>
    <div class="logo">
        <div style="display: flex; align-items: center;">
            <img src="OIP.jpeg" alt="MyPetakom Logo">
            <div class="logo-text">
                <h3>Welcome, <?php echo htmlspecialchars($cdName);?> (Coordinator)</h3>
            </div>
        </div>
        <h2>MyPetakom</h2>
    </div>
    
    <div class="sidebar">
        <button class="active">Dashboard</button>
        <a href="cdProfile.php">
        <button>My Profile</button>
        </a>
        <a href="adMembershipDis.php">
             <button>Membership Approvals</button>
        </a>
        <button>Event Management</button>
        <button>Merit Approvals</button>
        <button>Reports</button>
        <button onclick="confirmLogout()">LogOut</button>
    </div>
    
    <div class="content">
        <div class="dashboard-header">Dashboard Overview</div>
        
        <div class="dashboard-grid">
            <!-- Event Attendance Chart -->
            <div class="graph-container">
                <div class="chart-title">Event Attendance Overview</div>
                <div class="chart-container">
                    <canvas id="eventAttendanceChart"></canvas>
                </div>
            </div>
            
            <!-- Right column -->
            <div class="right-column">
                <!-- Pending Approvals Section -->
                <div class="approvals-section">
                    <div class="section-title">Pending Approvals</div>
                    <div class="approval-item">
                        <div>
                            <div class="approval-count"><?php echo $pendingMemberships; ?></div>
                            <div class="approval-label">Membership Applications</div>
                        </div>
                    </div>
                    <div class="approval-item">
                        <div>
                            <div class="approval-count"><?php echo $pendingMerits; ?></div>
                            <div class="approval-label">Event Merit Requests</div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Section -->
                <div class="stats-section">
                    <div class="section-title">System Statistics</div>
                    <div class="stats-item">
                        <span class="stats-label">Total Students:</span>
                        <span class="stats-value"><?php echo $totalStudents; ?></span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-label">Total Events:</span>
                        <span class="stats-value"><?php echo $totalEvents; ?></span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-label">Total Attendances:</span>
                        <span class="stats-value"><?php echo $totalAttendance; ?></span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-label">Active Participants:</span>
                        <span class="stats-value"><?php echo $activeParticipants; ?></span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-label">Participation Rate:</span>
                        <span class="participation-rate">
                            <?php echo $totalStudents > 0 ? round(($activeParticipants / $totalStudents) * 100, 1) : 0; ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
        <script>
        // Event Attendance Chart
        const ctx = document.getElementById('eventAttendanceChart').getContext('2d');
        const eventAttendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($eventNames); ?>,
                datasets: [{
                    label: 'Number of Attendees',
                    data: <?php echo json_encode($attendeeCounts); ?>,
                    backgroundColor: [
                        '#1976D2',
                        '#2196F3', 
                        '#64B5F6',
                        '#90CAF9',
                        '#BBDEFB',
                        '#1565C0',
                        '#0D47A1',
                        '#42A5F5',
                        '#1E88E5',
                        '#0277BD'
                    ],
                    borderColor: '#1976D2',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: '#1976D2',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#666'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        title: {
                            display: true,
                            text: 'Number of Attendees',
                            color: '#333',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            color: '#666',
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Events',
                            color: '#333',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    </script>
</body>
</html>

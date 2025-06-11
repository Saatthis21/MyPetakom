<?php
session_start();

// Show alert message if available
if (isset($_SESSION['message']) && $_SESSION['message_type'] === 'success') {
    $message = $_SESSION['message'];
    echo "<script>alert('$message');</script>";
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Check if user is logged in as student
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Student') {
    header("Location: LoginPage.html");
    exit();
}

$loginID = $_SESSION['loginID']; // From session

// Fetch student name from DB
$host = 'localhost';
$dbname = 'login_system';
$usernameDB = 'root';
$passwordDB = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $usernameDB, $passwordDB);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT student_name FROM student WHERE loginID = ?");
    $stmt->execute([$loginID]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    $studentName = $student ? $student['student_name'] : 'Student';

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
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
    <title>Student Dashboard</title>
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
    body { background-color: #f5f5f5; }

    .logo {
        background-color: aquamarine;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: fixed; /* Corrected from position: flex */
        width: 100%; /* Full width */
        top: 0; /* Align to the top */
        left: 0; /* Align to the left edge */
        z-index: 10; /* Keep it above other content */
    }

    .logo img { width: 100px; height: 100px; }
    .logo-text { margin-left: 15px; }

    .sidebar {
        width: 200px;
        background-color: aquamarine;
        height: 100vh;
        position: fixed;
        padding-top: 120px; /* Adjusted to account for header height (~110px) */
        top: 0; /* Align with the top */
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
        text-align: center
        
    }

    .sidebar button:hover,
    .sidebar .active {
        background-color: cadetblue; /* Active and hover state */
        color: black; /* Better contrast */
        font-weight: bold; /* Make active button stand out */
    }

    .content {
        margin-left: 220px;
        padding: 20px;
        margin-top: 110px; /* Adjusted for the fixed header height */
    }

    .dashboard-header {
        font-size: 22px;
        margin-bottom: 30px;
    }

    .card {
        background: white;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .card h3 {
        margin-bottom: 10px;
    }

    .button {
        padding: 10px 15px;
        margin-top: 10px;
        background-color: #2196F3;
        border: none;
        color: white;
        border-radius: 5px;
        cursor: pointer;
    }

    .button:hover {
        background-color: #0b7dda;
    }

    .events {
        margin-top: 20px;
        padding: 20px;
        border-radius: 8px;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
</style>
</head>

<body>

<div class="logo">
    <div style="display: flex; align-items: center;">
        <img src="OIP.jpeg" alt="MyPetakom Logo">
        <div class="logo-text">
            <h3>Welcome, <?php echo htmlspecialchars($studentName); ?> (Student)</h3>
        </div>
    </div>
    <h2>MyPetakom</h2>
</div>

<div class="sidebar">
    <button class="active">Dashboard</button>
    <a href="userProfile.php">
    <button>My Profile</button>
    </a>
    <a href="stMembership.php">
    <button>Apply Membership</button>
    </a>
    <a href="stMembershipStatus.php">
    <button>Membership Status</button>
    </a>
    <a href="generateQR.php">
    <button>My Event</button>
    </a>
    <a href="st.MembershipStatus.php"></a>
    <button>My Merits</button>
    <button onclick="confirmLogout()">LogOut</button>
</div>

<div class="content">
    <div class="dashboard-header">Dashboard</div>

    <div class="card">
        <h3>Membership Status: <span style="color: green;">Approved</span></h3>
        <p>Expired: 2025-12-31</p>

        <button class="button">Upload New Student Card</button><br>
        <a href="stMembershipStatus.php">
        <button class="button">View Membership Certificate</button>
        </a>
    </div>

    <div class="events">
        <h3>Upcoming Events</h3>
        <ul>
            <li>Event 1 (2025-06-01)</li>
            <li>Event 2 (2025-06-15)</li>
        </ul>
    </div>
</div>

</body>
</html>

<?php
session_start();

// Only Coordinators can access
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Coordinator') {
    header("Location: LoginPage.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "login_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get pending memberships and student info
$sql = "
    SELECT m.*, s.student_name, s.student_number 
    FROM membership m
    JOIN login l ON m.loginID = l.loginID
    JOIN student s ON s.loginID = l.loginID
    WHERE m.status = 'Pending'
";

$result = $conn->query($sql);
if (!$result) {
    die("Query error: " . $conn->error);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Application</title>
    <style>
        /* Your full original CSS kept as-is */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background-color: #f5f5f5;
        }

        .logo {
            background-color: #1976D2;
            padding: 10px;
            display: flex;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 10;
            color: white;
            justify-content: space-between;
            
        }
        

        img {
            width: 100px;
            height: 1o0px;
            display: inline-block;
        }

    .logo-text { 
        margin-left: 15px; 
    }

        .sidebar {
            width: 200px;
            background-color: #2196F3;
            color: white;
            padding: 0px;
            height: 100%;
            position: fixed;
            top: 0;
            padding-top: 130px;
        }

        .navButton, .navButtonactive {
            display: block;
            width: 100%;
            padding: 30px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: black;
            transition: background-color 0.3s;
        }

        .navButton:hover {
            background-color: #0D47A1;
        }

        .navButtonactive {
            background-color:#0D47A1;
            color: black;
            font-weight: bold;
        }

        .content {
            margin-left: 230px;
            padding: 20px;
            margin-top: 120px;
        }

        .input {
            margin-left: 20px;
            margin-top: 0px;
        }

        .form {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        h1 {
            margin-left: 35%;
            padding-bottom: 50px;
        }

        .input {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .action-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .action-btn:hover {
            background-color: #0b7dda;
        }

        th {
            margin-right: 20px;
            padding-right: 260px;
        }
    </style>
</head>
<body>
<div class="logo">
        <div style="display: flex; align-items: center;">
            <img src="OIP.jpeg" alt="MyPetakom Logo">
            <div class="logo-text">
                <h3>Welcome</h3>
            </div>
        </div>
        <h2>MyPetakom</h2>
    </div>

    <div class="sidebar">
        <a href="cdDashboard.php"><button class="navButton">Dashboard</button></a>
        <a href="cdProfile.php"><button class="navButton">Profile</button></a>
        <button class="navButtonactive">Membership Approval</button>
        <button class="navButton">Event Management</button>
        <button class="navButton">Merits Approvals</button>
        <button class="navButton">Reports</button>
        <button onclick="confirmLogout()" class="navButton">LogOut</button>
    </div>

    <div class="content">
        <h4>Pending Approval List</h4>
        <div class="form">
            <form>
            <table>
    <tr>
        <th>Matric ID</th>
        <th>Name</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['student_number']); ?></td>
        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
        <td>
            <a class="action-btn" href="cdMembershipDetails.php?id=<?php echo $row['membershipID']; ?>">View</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
            </form>
        </div>
    </div>
</body>
</html>

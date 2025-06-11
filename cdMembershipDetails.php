<?php
session_start();

// Only Coordinators can access
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Coordinator') {
    header("Location: LoginPage.php");
    exit();
}

// Ensure we have a membership ID (either GET or POST)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] <= 0) {
        die("Invalid request. No membership ID provided.");
    }
    $membershipID = intval($_GET['id']);
} else {
    // POST
    if (empty($_POST['membershipID']) || !is_numeric($_POST['membershipID']) || $_POST['membershipID'] <= 0) {
        die("Invalid membership ID.");
    }
    $membershipID = intval($_POST['membershipID']);
}

$conn = new mysqli("localhost", "root", "", "login_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if (!in_array($action, ['approve','reject'])) {
        die("Invalid action.");
    }
    $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
    $updateSQL = "
      UPDATE membership
      SET status = ?, approval_date = CURDATE()
      WHERE membershipID = ?
    ";
    $stmt = $conn->prepare($updateSQL);
    $stmt->bind_param("si", $newStatus, $membershipID);
    if ($stmt->execute()) {
        // JS alert + redirect
        echo "<script>
                alert('Membership {$newStatus} successfully.');
                window.location.href = 'adMembershipDis.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('Failed to update status.');</script>";
    }
    $stmt->close();
}

// Fetch the application for display
$sql = "
    SELECT m.*, s.student_name, s.student_number, s.studentID, l.username
    FROM membership m
    JOIN login l ON m.loginID = l.loginID
    JOIN student s ON s.loginID = l.loginID
    WHERE m.membershipID = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $membershipID);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    die("Membership application not found.");
}
$row = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <title>Membership Details - MyPetakom</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
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
            justify-content: space-between;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 10;
            color: white;
        }

        .logo img {
            width: 110px;
            height: 110px;
        }

        .logo-text {
            margin-left: 15px;
        }

        .logo-text h1 {
            font-size: 26px;
            margin-bottom: 5px;
        }

        .logo-text h5 {
            font-size: 14px;
            font-weight: normal;
        }

        .sidebar {
            width: 200px;
            background-color: #2196F3;
            color: white;
            position: fixed;
            top: 0;
            height: 100%;
            padding-top: 130px;
        }

        .navButton, .navButtonactive {
            display: block;
            width: 100%;
            padding: 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            text-align: left;
            color: black;
            transition: background-color 0.3s;
        }

        .navButton:hover {
            background-color: #0D47A1;
        }

        .navButtonactive {
            background-color: #0D47A1;
            font-weight: bold;
        }

        .content {
            margin-left: 230px;
            padding: 20px;
            margin-top: 130px;
        }

        .form {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .membership-container {
            display: flex;
            gap: 40px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .student-details {
            flex: 1;
            min-width: 250px;
        }

        .field-row {
            margin-bottom: 20px;
        }

        .field-label {
            display: block;
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .field-input {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        .card-image {
            width: 200px;
            height: 250px;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .profile-link {
            display: inline-block;
            margin-top: 10px;
            font-size: 14px;
            color: #2196F3;
            text-decoration: none;
        }

        .profile-link:hover {
            text-decoration: underline;
        }

        .action-buttons {
            margin-top: 25px;
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-approve {
            background-color: #4CAF50;
            color: white;
        }

        .btn-approve:hover {
            background-color: #45a049;
        }

        .btn-reject {
            background-color: #f44336;
            color: white;
        }

        .btn-reject:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
  <div class="logo">
    <div style="display:flex;align-items:center;">
      <img src="OIP.jpeg" alt="Logo">
      <h3 style="margin-left:15px;">Welcome, <?php echo htmlspecialchars($row['username']); ?></h3>
    </div>
    <h2>MyPetakom</h2>
  </div>

  <div class="sidebar">
    <a href="cdDashboard.php"><button class="navButton">Dashboard</button></a>
    <a href="cdProfile.php"><button class="navButton">Profile</button></a>
    <button class="navButton">Add User and Delete</button>
    <button class="navButtonactive">Membership Approval</button>
    <button class="navButton">Event Management</button>
    <button class="navButton">Merits Approvals</button>
    <button class="navButton">Reports</button>
    <button onclick="confirmLogout()">LogOut</button>
  </div>

  <div class="content">
    <h2>Membership Application Details</h2>
    <div class="form">
      <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']).'?id='.$membershipID; ?>"
            onsubmit="return confirm('Are you sure you want to perform this action?');">
        <div class="membership-container">
          <div class="student-details">
            <div class="field-row">
              <label class="field-label">Student Name</label>
              <input class="field-input" value="<?php echo htmlspecialchars($row['student_name']); ?>" readonly>
            </div>
            <div class="field-row">
              <label class="field-label">Student Username</label>
              <input class="field-input" value="<?php echo htmlspecialchars($row['username']); ?>" readonly>
            </div>
            <div class="field-row">
              <label class="field-label">Matric Number</label>
              <input class="field-input" value="<?php echo htmlspecialchars($row['student_number']); ?>" readonly>
            </div>
            <div class="field-row">
              <label class="field-label">Status</label>
              <input class="field-input" value="<?php echo htmlspecialchars($row['status']); ?>" readonly>
            </div>
            

            <input type="hidden" name="membershipID" value="<?php echo $membershipID;?>">
            <div class="action-buttons">
              <button type="submit" name="action" value="approve" class="btn btn-approve">Approve</button>
              <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
            </div>
          </div>
          <div class="card-image">
            <?php if (!empty($row['matric_id_pic']) && file_exists($row['matric_id_pic'])): ?>
              <img src="<?php echo htmlspecialchars($row['matric_id_pic']); ?>" alt="Matric Card">
            <?php else: ?>
              <span>No Image</span>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
<?php
session_start();

// Only logged-in students can access
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Student') {
    header("Location: LoginPage.php");
    exit();
}

$loginID = intval($_SESSION['loginID']);

// Database connection
$conn = new mysqli("localhost", "root", "", "login_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch this student's membership application and student info
$sql = "
  SELECT 
    s.student_name,
    s.student_number,
    s.student_email,
    m.application_date,
    m.approval_date,
    m.status,
    m.matric_id_pic
  FROM student s
  JOIN membership m ON s.studentID = m.studentID
  WHERE s.loginID = ?
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $loginID);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("You have not submitted a membership application yet.");
}

$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

$username = $_SESSION['username'];
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
    <title>My Membership Status</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f5f5f5; }

        .logo {
            background-color: aquamarine;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 10;
        }

        .logo img { width: 100px; height: 100px; }
        .logo-text { margin-left: 15px; }

        .sidebar {
            width: 200px;
            background-color: aquamarine;
            height: 100vh;
            position: fixed;
            padding-top: 120px;
            top: 0;
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
            background-color: cadetblue;
            color: black;
            font-weight: bold;
        }

        .sidebar a {
            text-decoration: none;
            display: block;
        }

        .content {
            margin-left: 220px;
            padding: 20px;
            margin-top: 110px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .card h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
            font-size: 22px;
        }

        .field {
            margin-bottom: 15px;
        }

        .label {
            font-weight: bold;
            color: #333;
        }

        .value {
            margin-top: 5px;
            color: #555;
        }

        .card-image {
            margin: 20px auto;
            text-align: center;
        }

        .card-image img {
            max-width: 300px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .status {
            font-size: 18px;
            margin-top: 20px;
            text-align: center;
        }

        .status span {
            font-weight: bold;
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
    <button class="active">Dashboard</button>
    <a href="userProfile.php">
    <button>My Profile</button>
    </a>
    <a href="stMembership.php">
    <button>Apply Membership</button>
    </a>
    <a href="st.MembershipStatus.php">
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
        <div class="card">
            <h2>Your Membership Application</h2>

            <div class="field">
                <div class="label">Full Name</div>
                <div class="value"><?php echo htmlspecialchars($row['student_name']); ?></div>
            </div>

            <div class="field">
                <div class="label">Matric Number</div>
                <div class="value"><?php echo htmlspecialchars($row['student_number']); ?></div>
            </div>

            <div class="field">
                <div class="label">Email</div>
                <div class="value"><?php echo htmlspecialchars($row['student_email']); ?></div>
            </div>

            <div class="field">
                <div class="label">Application Date</div>
                <div class="value"><?php echo date('F j, Y', strtotime($row['application_date'])); ?></div>
            </div>

            <?php if (!empty($row['approval_date'])): ?>
            <div class="field">
                <div class="label">Approval Date</div>
                <div class="value"><?php echo date('F j, Y', strtotime($row['approval_date'])); ?></div>
            </div>
            <?php endif; ?>

            <div class="card-image">
                <?php if (!empty($row['matric_id_pic']) && file_exists($row['matric_id_pic'])): ?>
                    <img src="<?php echo htmlspecialchars($row['matric_id_pic']); ?>" alt="Matric Card">
                <?php else: ?>
                    <div>No ID Card Image Uploaded</div>
                <?php endif; ?>
            </div>

            <div class="status">
                Current Status: <span><?php echo htmlspecialchars($row['status']); ?></span>
            </div>
        </div>
    </div>

</body>
</html>
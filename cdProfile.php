<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Coordinator') {
    header("Location: LoginPage.php");
    exit();
}

$loginID = $_SESSION['loginID'];
$username = $_SESSION['username'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=login_system;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_changes'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $staffID = $_POST['staffID'] ?? '';

        try {
            $stmt = $pdo->prepare("UPDATE coordinator SET coordinator_name=?, coordinator_email=?, staffID=? WHERE loginID=?");
            $stmt->execute([$name, $email, $staffID, $loginID]);
            $message = "Profile updated successfully.";
        } catch (PDOException $e) {
            $message = "Error updating profile: " . $e->getMessage();
        }
    }
    
}

// Fetch coordinator data
$stmt = $pdo->prepare("SELECT coordinator_name, coordinator_email, staffID FROM coordinator WHERE loginID=?");
$stmt->execute([$loginID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User data not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coordinator Profile</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f5f5f5; }

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

        .logo img { width: 100px; height: 100px; }
        .logo-text { margin-left: 15px; }

        .sidebar {
            width: 200px;
            background-color: #2196F3;
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
            background-color: #0D47A1;
            color: black;
            font-weight: bold;
        }

        .content {
            margin-left: 220px;
            padding: 20px;
            margin-top: 130px;
            width: 60%;
        }

        .dashboard-header {
            font-size: 22px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: white;
            padding: 20px;
            margin: 50px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-field {
            margin-bottom: 15px;
        }

        .profile-field label {
            display: inline-block;
            width: 100px;
            font-weight: bold;
        }

        .profile-field input[type="text"] {
            width: 200px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 10px;
        }

        .profile-field .edit-btn {
            padding: 5px 10px;
            background-color: #2196F3;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .profile-field .edit-btn:hover {
            background-color: #0b7dda;
        }

        .action-buttons {
            margin-top: 20px;
            margin-bottom: 50px;
        }

        .action-btn {
            padding: 10px 15px;
            margin-right: 10px;
            background-color: #2196F3;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .action-btn:hover {
            background-color: #0b7dda;
        }

        .admin-only {
           
        }

        .message {
            color: green;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = "logout.php";
            }
        }

        function enableEdit() {
            document.querySelectorAll('.profile-field input').forEach(input => {
                input.readOnly = false;
            });
        }
    </script>
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
    <a href="cdDashboard.php"><button>Dashboard</button></a>
    <button class="active">Profile</button>
    <a href="adMembershipDis.php"><button>Membership Approvals</button></a>
    <button>Event Management</button>
    <button>Merit Approvals</button>
    <button>Reports</button>
    <button onclick="confirmLogout()">LogOut</button>
</div>

<div class="content">
    <div class="dashboard-header">User Profile</div>
    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="profile-card">
            <div class="profile-field">
                <label>Account Type:</label>
                <input type="text" value="Coordinator" readonly>
            </div>
            <div class="profile-field">
                <label>Name:</label>
                <input name="name" type="text" value="<?php echo htmlspecialchars($user['coordinator_name']); ?>" readonly>
                <button type="button" class="edit-btn" onclick="enableEdit()">Edit</button>
            </div>
            <div class="profile-field">
                <label>Email:</label>
                <input name="email" type="text" value="<?php echo htmlspecialchars($user['coordinator_email']); ?>" readonly>
            </div>
            <div class="profile-field">
                <label>Staff ID:</label>
                <input name="staffID" type="text" value="<?php echo htmlspecialchars($user['staffID']); ?>" readonly>
            </div>

            <div class="action-buttons">
                <button class="action-btn" type="button">Change Password</button>
                
                <button class="action-btn" name="save_changes" type="submit" style="margin-left: 20px;">Save Changes</button>
                
                <a href="cdNewUser.php">
                <button class="action-btn admin-only" type="button" style="margin-left: 160px;">Add User</button>
                </a>
            </div>
        </div>
    </form>
</div>
</body>
</html>

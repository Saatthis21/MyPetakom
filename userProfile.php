<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Student') {
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
        $matric = $_POST['matric'] ?? '';
        $email = $_POST['email'] ?? '';

        try {
            $stmt = $pdo->prepare("UPDATE student SET student_name=?, student_number=?, student_email=? WHERE loginID=?");
            $stmt->execute([$name, $matric, $email, $loginID]);
            $message = "Profile updated successfully.";
        } catch (PDOException $e) {
            $message = "Error updating profile: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_account'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM student WHERE loginID=?");
            $stmt->execute([$loginID]);

            $stmt = $pdo->prepare("DELETE FROM login WHERE loginID=?");
            $stmt->execute([$loginID]);

            session_destroy();
            header("Location: LoginPage.php");
            exit();
        } catch (PDOException $e) {
            $message = "Error deleting account: " . $e->getMessage();
        }
    }
}

// Fetch current student data
$stmt = $pdo->prepare("SELECT student_name, student_number, student_email FROM student WHERE loginID=?");
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
    <title>User Profile</title>
    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = "logout.php";
            }
        }

        function enableEdit() {
            document.querySelectorAll('.profile-field input').forEach(i => i.readOnly = false);
            document.getElementById('saveBtn').style.display = 'inline-block';
        }

        function confirmDelete() {
            if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
                document.getElementById('deleteForm').submit();
            }
        }

        function showUnavailableAlert() {
            alert("This feature is not available yet.");
        }
    </script>
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
            height: auto;
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
            margin-top: 20px;
        }

        .edit-btn {
            padding: 5px 10px;
            background-color: #2196F3;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .edit-btn:hover {
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
            display: none;
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
    <a href="stDashboard.php"><button>Dashboard</button></a>
    <a href="studentProfile.php"><button class="active">Profile</button></a>
    <a href="stMembership.php"><button>Apply Membership</button></a>
    <a href="stMembershipStatus.php"><button>Membership Status</button></a>
    <button >My Event</button>
    <button >My Merits</button>
    <button onclick="confirmLogout()">LogOut</button>
</div>

<div class="content">
    <div class="dashboard-header">User Profile</div>

    <?php if (!empty($message)): ?>
        <p style="color:green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="profile-card">
            <div class="profile-field">
                <label>Account Type:</label>
                <input type="text" value="Student" readonly>
            </div>
            <div class="profile-field">
                <label>Name:</label>
                <input name="name" type="text" value="<?= htmlspecialchars($user['student_name']) ?>" readonly>
                <button type="button" class="edit-btn" onclick="enableEdit()">Edit</button>
            </div>
            <div class="profile-field">
                <label>Matric:</label>
                <input name="matric" type="text" value="<?= htmlspecialchars($user['student_number']) ?>" readonly>
            </div>
            <div class="profile-field">
                <label>Email:</label>
                <input name="email" type="text" value="<?= htmlspecialchars($user['student_email']) ?>" readonly>
            </div>
            <div class="action-buttons">
                <button type="submit" id="saveBtn" name="save_changes" class="action-btn" style="display:none;">Save Changes</button>
                <button type="button" class="action-btn" onclick="window.location.href='changePassword.php'">Change Password</button>
                <button type="button" class="action-btn" onclick="confirmDelete()" style="margin-left: 550px;">Delete Account</button>
      
            </div>
        </div>
    </form>

    <form method="post" id="deleteForm">
        <input type="hidden" name="delete_account" value="1">
    </form>
</div>
</body>
</html>

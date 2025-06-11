<?php
session_start();

// Check if user is logged in as coordinator/admin
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
$users = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $searchTerm = $_POST['searchTerm'] ?? '';
        if (!empty($searchTerm)) {
            // Search in student, eventadvisor, and coordinator tables
            $stmt = $pdo->prepare("
                SELECT 'Student' as account_type, student_name as name, student_number as matric, student_email as email, studentID as id
                FROM student 
                WHERE student_name LIKE ? OR student_number LIKE ? OR student_email LIKE ?
                UNION
                SELECT 'eventAdvisor' as account_type, advisor_name as name, staffID as matric, advisor_email as email, advisorID as id
                FROM eventadvisor 
                WHERE advisor_name LIKE ? OR staffID LIKE ? OR advisor_email LIKE ?
                UNION
                SELECT 'Coordinator' as account_type, coordinator_name as name, staffID as matric, coordinator_email as email, coordinatorID as id
                FROM coordinator 
                WHERE coordinator_name LIKE ? OR staffID LIKE ? OR coordinator_email LIKE ?
            ");
            $searchPattern = "%$searchTerm%";
            $stmt->execute([
                $searchPattern, $searchPattern, $searchPattern, // Student params
                $searchPattern, $searchPattern, $searchPattern, // eventAdvisor params
                $searchPattern, $searchPattern, $searchPattern  // Coordinator params
            ]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    if (isset($_POST['delete_account'])) {
        $accountType = $_POST['account_type'] ?? '';
        $userId = $_POST['user_id'] ?? '';
        
        if ($accountType === 'Student' && !empty($userId)) {
            try {
                // Get loginID before deleting student record
                $stmt = $pdo->prepare("SELECT loginID FROM student WHERE studentID = ?");
                $stmt->execute([$userId]);
                $loginData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($loginData) {
                    $pdo->beginTransaction();
                    
                    // Delete from student table first
                    $stmt = $pdo->prepare("DELETE FROM student WHERE studentID = ?");
                    $stmt->execute([$userId]);
                    
                    // Delete from login table
                    $stmt = $pdo->prepare("DELETE FROM login WHERE loginID = ?");
                    $stmt->execute([$loginData['loginID']]);
                    
                    $pdo->commit();
                    $message = "Student account deleted successfully.";
                } else {
                    $message = "Student not found.";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error deleting student account: " . $e->getMessage();
            }
        } elseif ($accountType === 'eventAdvisor' && !empty($userId)) {
            try {
                // Get loginID before deleting advisor record
                $stmt = $pdo->prepare("SELECT loginID FROM eventadvisor WHERE advisorID = ?");
                $stmt->execute([$userId]);
                $loginData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($loginData) {
                    $pdo->beginTransaction();
                    
                    // Delete from eventadvisor table first
                    $stmt = $pdo->prepare("DELETE FROM eventadvisor WHERE advisorID = ?");
                    $stmt->execute([$userId]);
                    
                    // Delete from login table
                    $stmt = $pdo->prepare("DELETE FROM login WHERE loginID = ?");
                    $stmt->execute([$loginData['loginID']]);
                    
                    $pdo->commit();
                    $message = "Event Advisor account deleted successfully.";
                } else {
                    $message = "Event Advisor not found.";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error deleting event advisor account: " . $e->getMessage();
            }
        } elseif ($accountType === 'Coordinator' && !empty($userId)) {
            try {
                // Get loginID before deleting coordinator record
                $stmt = $pdo->prepare("SELECT loginID FROM coordinator WHERE coordinatorID = ?");
                $stmt->execute([$userId]);
                $loginData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($loginData) {
                    $pdo->beginTransaction();
                    
                    // Delete from coordinator table first
                    $stmt = $pdo->prepare("DELETE FROM coordinator WHERE coordinatorID = ?");
                    $stmt->execute([$userId]);
                    
                    // Delete from login table
                    $stmt = $pdo->prepare("DELETE FROM login WHERE loginID = ?");
                    $stmt->execute([$loginData['loginID']]);
                    
                    $pdo->commit();
                    $message = "Coordinator account deleted successfully.";
                } else {
                    $message = "Coordinator not found.";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error deleting coordinator account: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['add_user'])) {
        $accountType = $_POST['new_account_type'] ?? '';
        $name = $_POST['new_name'] ?? '';
        $matric = $_POST['new_matric'] ?? '';
        $email = $_POST['new_email'] ?? '';
        $username = $_POST['new_username'] ?? '';
        $password = $_POST['new_password'] ?? '';

        if (!empty($accountType) && !empty($name) && !empty($matric) && !empty($email) && !empty($username) && !empty($password)) {
            try {
                $pdo->beginTransaction();

                // Step 1: Insert into login table
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmtLogin = $pdo->prepare("INSERT INTO login (Username, password, role) VALUES (?, ?, ?)");
                $stmtLogin->execute([$username, $hashedPassword, $accountType]);
                $newLoginID = $pdo->lastInsertId();

                // Step 2: Insert into appropriate table based on account type
                if ($accountType === 'Student') {
                    $stmt = $pdo->prepare("INSERT INTO student (student_name, student_number, student_email, loginID) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $matric, $email, $newLoginID]);
                    $message = "Student account created successfully.";
                } elseif ($accountType === 'eventAdvisor') {
                    $stmt = $pdo->prepare("INSERT INTO eventadvisor (advisor_name, staffID, advisor_email, loginID) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $matric, $email, $newLoginID]);
                    $message = "Event Advisor account created successfully.";
                } elseif ($accountType === 'Coordinator') {
                    $stmt = $pdo->prepare("INSERT INTO coordinator (coordinator_name, staffID, coordinator_email, loginID) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $matric, $email, $newLoginID]);
                    $message = "Coordinator account created successfully.";
                } else {
                    $message = "Invalid account type.";
                }

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error creating account: " . $e->getMessage();
            }
        } else {
            $message = "All fields are required.";
        }
    }
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
        
        function confirmDelete(name) {
            return confirm("Are you sure you want to delete the account for " + name + "?");
        }
    </script>
    
    <meta charset="UTF-8">
    <title>Add User and Delete (Admin Only)</title>
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

        .sidebar a {
            text-decoration: none;
            display: block;
        }

        .content {
            margin-left: 220px;
            padding: 20px;
            margin-top: 110px;
        }

        .dashboard-header {
            font-size: 22px;
            margin-bottom: 30px;
            color: #333;
        }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-section {
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            max-width: 500px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: inline-block;
            width: 120px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 300px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .button {
            padding: 10px 15px;
            margin: 5px;
            background-color: #2196F3;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .button:hover {
            background-color: #0b7dda;
        }

        .button.delete {
            background-color: #f44336;
        }

        .button.delete:hover {
            background-color: #d32f2f;
        }

        .user-result {
            background: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }

        .user-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: center;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .add-user-section {
            border-top: 2px solid #eee;
            padding-top: 20px;
            margin-top: 30px;
        }
    </style>
</head>

<body>

<div class="logo">
    <div style="display: flex; align-items: center;">
        <img src="OIP.jpeg" alt="MyPetakom Logo">
        <div class="logo-text">
            <h3>Welcome, <?php echo htmlspecialchars($username); ?> (Coordinator)</h3>
        </div>
    </div>
    <h2>MyPetakom</h2>
</div>

<div class="sidebar">
    <a href="cdDashboard.php">
        <button>Dashboard</button>
    </a>
    <a href="coordinatorProfile.php">
        <button>Profile</button>
    </a>
    <a href="adMembershipDis.php">
        <button>Membership Approvals</button>
    </a>
    <button>Event Management</button>
    <button>Merit Approvals</button>
    <button>Reports</button>
    <button class="active">Add User and Delete</button>
    <button onclick="confirmLogout()">LogOut</button>
</div>

<div class="content">
    <div class="dashboard-header">Add User and Delete (Admin Only)</div>
    
    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Search Section -->
    <div class="card search-section">
        <h3>Search Users</h3>
        <form method="post">
            <input type="text" name="searchTerm" class="search-input" placeholder="Search by name, matric/staff ID, or email..." value="<?php echo htmlspecialchars($_POST['searchTerm'] ?? ''); ?>">
            <button type="submit" name="search" class="button">Search</button>
        </form>
        
        <?php if (!empty($users)): ?>
            <div style="margin-top: 20px;">
                <h4>Search Results:</h4>
                <?php foreach ($users as $user): ?>
                    <div class="user-result">
                        <div class="user-info">
                            <div>
                                <strong>Account Type:</strong><br>
                                <?php echo htmlspecialchars($user['account_type']); ?>
                            </div>
                            <div>
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </div>
                            <div>
                                <strong><?php echo $user['account_type'] === 'Student' ? 'Matric:' : 'Staff ID:'; ?></strong><br>
                                <?php echo htmlspecialchars($user['matric']); ?>
                            </div>
                            <div>
                                <strong>Email:</strong><br>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                            <div>
                                <form method="post" onsubmit="return confirmDelete('<?php echo htmlspecialchars($user['name']); ?>')">
                                    <input type="hidden" name="account_type" value="<?php echo htmlspecialchars($user['account_type']); ?>">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <button type="submit" name="delete_account" class="button delete">Delete Account</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add User Section -->
    <div class="card add-user-section">
        <h3>Add New User</h3>
        <form method="post">
    <div class="form-group">
        <label>Account Type:</label>
        <select name="new_account_type" required>
            <option value="">Select Account Type</option>
            <option value="Student">Student</option>
            <option value="eventAdvisor">Event Advisor</option>
        </select>
    </div>

    <div class="form-group">
        <label>Name:</label>
        <input type="text" name="new_name" required>
    </div>

    <div class="form-group">
        <label>Matric/Staff ID:</label>
        <input type="text" name="new_matric" required>
    </div>

    <div class="form-group">
        <label>Email:</label>
        <input type="email" name="new_email" required>
    </div>

    <div class="form-group">
        <label>Username:</label>
        <input type="text" name="new_username" required>
    </div>

    <div class="form-group">
        <label>Password:</label>
        <input type="password" name="new_password" required>
    </div>

    <button type="submit" name="add_user" class="button">Add User</button>
</form>
    </div>
</div>

</body>
</html>
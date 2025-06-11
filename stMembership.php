<?php
session_start();

// Only Students may apply
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Student') {
    header("Location: LoginPage.php");
    exit();
}

// Connect to DB
$conn = new mysqli("localhost", "root", "", "login_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$message_type = '';
$targetDir = "uploads/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate file upload
    if (!isset($_FILES['id']) || $_FILES['id']['error'] !== UPLOAD_ERR_OK) {
        $message = "Please select a file to upload.";
        $message_type = "error";
    } else {
        $tmp_name = $_FILES['id']['tmp_name'];
        $origName = basename($_FILES['id']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','bmp'];

        if (!in_array($ext, $allowed)) {
            $message = "Invalid file type. Only JPG/JPEG/PNG/GIF/BMP allowed.";
            $message_type = "error";
        } elseif (getimagesize($tmp_name) === false) {
            $message = "Uploaded file is not a valid image.";
            $message_type = "error";
        } else {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $newName = uniqid() . '_' . $origName;
            $destPath = $targetDir . $newName;

            if (move_uploaded_file($tmp_name, $destPath)) {
                // Get studentID of logged-in user
                $stmt = $conn->prepare("SELECT studentID FROM student s JOIN login l ON s.loginID = l.loginID WHERE l.Username = ?");
                $stmt->bind_param("s", $_SESSION['username']);
                $stmt->execute();
                $stmt->bind_result($studentID);
                if ($stmt->fetch()) {
                    $stmt->close();

                    // Insert membership application
                    $insertStmt = $conn->prepare("INSERT INTO membership (studentID, loginID, matric_id_pic, status, application_date) VALUES (?, (SELECT loginID FROM login WHERE Username = ?), ?, 'Pending', CURDATE())");
                    $insertStmt->bind_param("iss", $studentID, $_SESSION['username'], $destPath);
                    if ($insertStmt->execute()) {
                        $message = "Membership application submitted successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error inserting application: " . $insertStmt->error;
                        $message_type = "error";
                    }
                    $insertStmt->close();
                } else {
                    $message = "Student record not found.";
                    $message_type = "error";
                    $stmt->close();
                }
            } else {
                $message = "Failed to move uploaded file.";
                $message_type = "error";
            }
        }
    }

    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    header("Location: stMembership.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Apply Membership</title>
    <style>
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
        background-color: aquamarine;
        padding: 10px;
        display: flex;
        align-items: center;
        position: fixed; /* Make header fixed */
        width: 100%; /* Full width */
        top: 0; /* Align to the top */
        left: 0; /* Align to the left edge */
        z-index: 10; /* Keep it above other content */
    }

    img {
        width: 100px;
        height: 100px;
        display: inline-block;
    }

    .sidebar {
        width: 200px;
        background-color: aquamarine;
        color: black;
        padding: 0;
        height: 100vh;
        position: fixed;
        top: 0; /* Align with the top */
        padding-top: 130px; /* Adjusted for header height (~120px) */
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
        text-align: center;
        transition: background-color 0.3s;
    }

    .navButton:hover {
        background-color: cadetblue;
        font-weight: bold;
    }

    .navButtonactive {
        background-color: cadetblue;
        color: black;
        font-weight: bold;
        
    }

    .content {
        margin-left: 230px;
        padding: 20px;
        margin-top: 120px; /* Offset for the fixed header height */
    }

    .form {
        margin-top: 50px;
        margin-left: 110px;
        background-color: #f8f8f8;
        padding: 20px;
        border-radius: 10px;
        width: 70%;
    }

    h1 {
        margin-left: 35%;
        padding-bottom: 50px;
    }

    input[type="text"], input[type="password"], input[type="file"], select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        margin-top: 20px;
    }

    .actionbutton {
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 20px;
        width: 90px;
        margin-left: 75%;
        background-color: #2196F3;
        border: none;
        color: white;
    }

    .actionbutton:hover {
        background-color: #6eb9f7;
    }

    .message {
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        width: 70%;
        margin-left: 110px;
    }

    .success {
        background-color: #dff0d8;
        color: #3c763d;
    }

    .error {
        background-color: #f2dede;
        color: #a94442;
    }

    table {
        width: 100%;
    }

    td {
        padding: 8px;
    }
</style>
</head>
<body>
    <div class="logo">
        <img src="OIP.jpeg" alt="Logo" />
        <h5 style="display:inline;">Welcome </h5>
        <h1 style="display:inline; margin-left: 15px;">My Petakom</h1>
    </div>
    <div class="sidebar">
    <a href="stDashboard.php" ><button class="navButton">Dashboard</button></a>
    <a href="userProfile.php"><button class="navButton">Profile</button></a>
    <a href="stMembership.php"><button class="navButtonactive">Apply Membership</button></a>
    <a href="stMembershipStatus.php"><button class="navButton">Membership Status</button></a>
    <button class="navButton">My Event</button>
    <button class="navButton" >My Merits</button>
    <button class="navButton" onclick="confirmLogout()">LogOut</button>
    </div>

    <div class="content">
        <h4>My Petakom Membership Application</h4>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="form">
            <form action="stMembership.php" method="POST" enctype="multipart/form-data">
                <table>
                    <tr>
                        <td>Student Name:</td>
                        <td><input type="text" name="name" required></td>
                    </tr>
                    <tr>
                        <td>Matric Number:</td>
                        <td><input type="text" name="mnum" required></td>
                    </tr>
                    <tr>
                        <td>Student Password:</td>
                        <td><input type="password" name="password" required></td>
                        <td></td>
                        <td>Confirm Password</td>
                        <td><input type="password" name="cmpassword" required></td>
                    </tr>
                    <tr>
                        <td>Upload card image:</td>
                        <td><input type="file" name="id" accept="image/*" required></td>
                    </tr>
                </table>
                <button type="submit" class="actionbutton">Apply</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
session_start();

$host = 'localhost';
$dbname = 'login_system';
$usernameDB = 'root';
$passwordDB = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $usernameDB, $passwordDB);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if form data exists
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
   

    // Fetch user by username and role
    $sql = "SELECT * FROM login WHERE Username = ? AND role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists and password is correct
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['loginID'] = $user['loginID'];
        $_SESSION['loggedin'] = true;


        $_SESSION['message'] = "Login successful!";
        $_SESSION['message_type'] = "success";

        // Redirect based on role
        switch (strtolower($user['role'])) {
            case 'student':
                header("Location: stDashboard.php");
                break;
            case 'coordinator':
                header("Location: cdDashboard.php");
                break;
            case 'eventadvisor':
                header("Location: EaDashboard.php");
                break;
            default:
                header("Location: defaultDashboard.php");
                break;
        }
        exit();
    } else {
        $_SESSION['message'] = "Invalid credentials or role mismatch, please try again.";
        $_SESSION['message_type'] = "error";
        header("Location: LoginPage.php");
        exit();
    }
} else {
    // Redirect if accessed directly
    header("Location: LoginPage.php");
    exit();
}
?>

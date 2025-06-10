<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MyPetakom</title>
    <style>
        body { font-family: Arial, sans-serif; background-image: url('login-page-background.jpg'); background-size: cover; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; margin-bottom: 20px; }
        input, select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <select name="role" id="role" required>
                <option value="">-- Select Role --</option>
                <option value="Student">Student</option>
                <option value="Coordinator">Coordinator</option>
                <option value="EventAdvisor">EventAdvisor</option>
            </select>

            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="id_number" placeholder="Matric Number / Staff ID" required>

            <button type="submit">Create Account</button>
        </form>
    </div>
</body>
</html>

<?php
// register.php
session_start();

// DB config
$host = 'localhost';
$dbname = 'login_system';
$userDB = 'root';
$passDB = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $userDB, $passDB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username']);
    $password   = $_POST['password'];
    $role       = $_POST['role'];
    $name       = trim($_POST['name']);
    $email      = trim($_POST['email']);
    $id_number  = trim($_POST['id_number']);

    // Validate
    if (empty($username) || empty($password) || empty($role) || empty($name) || empty($email) || empty($id_number)) {
        die('Please fill all fields.');
    }

    // Check existing username
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login WHERE Username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        die('Username already taken.');
    }

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert into login table
    $insert = $pdo->prepare("INSERT INTO login (Username, password, role) VALUES (?, ?, ?)");
    $insert->execute([$username, $hash, $role]);
    $loginID = $pdo->lastInsertId();

    // Role-specific insert
    if ($role === "Student") {
        $stmt = $pdo->prepare("INSERT INTO student (loginID, student_name, student_number, student_email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$loginID, $name, $id_number, $email]);
    } elseif ($role === "Coordinator") {
        $stmt = $pdo->prepare("INSERT INTO coordinator (loginID, coordinator_name, staffID, coordinator_email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$loginID, $name, $id_number, $email]);
    } elseif ($role === "EventAdvisor") {
        $stmt = $pdo->prepare("INSERT INTO eventadvisor (loginID, advisor_name, staffID, advisor_email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$loginID, $name, $id_number, $email]);
    }

    // Success
    $_SESSION['message'] = 'Registration successful! You can now login.';
    $_SESSION['message_type'] = 'success';
    header('Location: LoginPage.php');
    exit;
}
?>

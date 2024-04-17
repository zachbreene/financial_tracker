<?php
session_start();  // Start the session at the very beginning

// Check if the user is already logged in
if (isset($_SESSION['userid'])) {
    header('Location: dashboard.php');  // Redirect to user dashboard if already logged in
    exit();
}

// Include the database connection file
require_once 'includes/database-connection.php';

$error = '';  // Variable to store error messages

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // SQL to check the existence of the user
    $sql = "SELECT userID, username, password FROM user WHERE username = ?";

    if ($stmt = $pdo->prepare($sql)) {
        $stmt->execute([$username]);  // Execute the query

        // Check if the user exists
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start the session
                $_SESSION['userid'] = $user['userID'];
                $_SESSION['username'] = $user['username'];

                // Redirect to the user dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Oops! Something went wrong. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if ($error != '') echo '<p style="color:red;">' . $error . '</p>'; ?>
    <form action="login.php" method="post">
        <div>
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>
</body>
</html>

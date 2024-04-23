<?php
ob_start(); // Start output buffering
session_start(); // Start the session

// Check if there's a success message to display
if (isset($_SESSION['success_message'])) {
    echo '<p style="color:green;">' . $_SESSION['success_message'] . '</p>';
    // Unset the success message after displaying it
    unset($_SESSION['success_message']);
}

// Include the database connection file
require_once 'includes/database-connection.php';

$error = ''; // Variable to store error messages

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['userEmail'], $_POST['password'])) {
    $userEmail = trim($_POST['userEmail']);
    $password = trim($_POST['password']); // The password the user entered
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : null;

    // SQL to check the existence of the user
    $sql = "INSERT INTO user (firstName, lastName, userEmail, phoneNumber, password) VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $pdo->prepare($sql)) {
        $stmt->execute([$firstName, $lastName, $userEmail, $phoneNumber, password_hash($password, PASSWORD_DEFAULT)]); // Execute the query

        // Redirect to login page with success message
        $_SESSION['success_message'] = 'Account Created Successfully! You can now login.';
        header('Location: index.php');
        exit();
    } else {
        $error = 'Oops! Something went wrong. Please try again later.';
    }
}
ob_end_flush(); // End buffering and flush all output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <h2>Create New Account</h2>
    <?php if ($error != '') echo '<p style="color:red;">' . $error . '</p>'; ?>
    <form action="register.php" method="post">
        <div>
            <label for="firstName">First Name:</label>
            <input type="text" name="firstName" id="firstName" required>
        </div>
        <div>
            <label for="lastName">Last Name:</label>
            <input type="text" name="lastName" id="lastName" required>
        </div>
        <div>
            <label for="userEmail">Email:</label>
            <input type="email" name="userEmail" id="userEmail" required>
        </div>
        <div>
            <label for="phoneNumber">Phone Number (optional):</label>
            <input type="text" name="phoneNumber" id="phoneNumber">
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        
        <!-- Security Questions fields -->
        <?php for ($i = 1; $i <= 3; $i++): ?>
        <div>
            <label for="question<?= $i ?>">Security Question <?= $i ?>:</label>
            <select name="question<?= $i ?>" id="question<?= $i ?>" required>
                <option value="">Select a question...</option>
                <?php foreach ($securityQuestions as $question): ?>
                    <option value="<?= htmlspecialchars($question['questionID']) ?>"><?= htmlspecialchars($question['questionText']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="answer<?= $i ?>" id="answer<?= $i ?>" required>
        </div>
        <?php endfor; ?>
        
        <div>
            <button type="submit">Create Account</button>
        </div>
    </form>
</body>
</html>

<?php
ob_start();       // Start output buffering
session_start();  // Start the session

// Include the database connection file
require_once 'includes/database-connection.php';

$error = '';  // Variable to store error messages

// Fetch security questions for the form
$questionsStmt = $pdo->query("SELECT questionID, questionText FROM security_questions");
$securityQuestions = $questionsStmt->fetchAll();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['userEmail'], $_POST['password'])) {
    $userEmail = trim($_POST['userEmail']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT); // Hash the password before storing it

    // Start the transaction
    $pdo->beginTransaction();
    try {
        // SQL to insert the new user
        $stmt = $pdo->prepare("INSERT INTO user (userEmail, password) VALUES (?, ?)");
        $stmt->execute([$userEmail, $password]);
        $userID = $pdo->lastInsertId(); // Get the last inserted ID for the user

        // Prepare security answers insertion
        $stmt = $pdo->prepare("INSERT INTO security_answers (userID, questionID, answerText) VALUES (?, ?, ?)");

        // Insert security answers
        for ($i = 1; $i <= 3; $i++) {
            if (isset($_POST["question$i"], $_POST["answer$i"])) {
                $questionID = $_POST["question$i"];
                $answer = trim($_POST["answer$i"]);
                $stmt->execute([$userID, $questionID, $answer]);
            }
        }

        // Commit the transaction
        $pdo->commit();

        // Set a session variable with the success message
        $_SESSION['success_message'] = 'Account Created!';

        // Redirect to login page after successful registration
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Error creating account: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <h2>Create New Account</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>
    <form action="register.php" method="post">
        <!-- Email and Password fields -->
        <div>
            <label for="userEmail">Email:</label>
            <input type="email" name="userEmail" id="userEmail" required>
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

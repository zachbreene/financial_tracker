<?php
ob_start(); // Start output buffering
session_start(); // Start the session

// Include the database connection file
require_once 'includes/database-connection.php';

$error = ''; // Variable to store error messages

// Fetch security questions for the form
$questionsStmt = $pdo->query("SELECT questionID, questionText FROM security_questions");
$securityQuestions = $questionsStmt->fetchAll();

// Function to check password strength
function isPasswordStrong($password) {
    return preg_match('/[A-Z]/', $password)      // at least one upper case
        && preg_match('/[a-z]/', $password)      // at least one lower case
        && preg_match('/\d/', $password)         // at least one digit
        || preg_match('/[^a-zA-Z\d]/', $password); // at least one special character
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['firstName'], $_POST['lastName'], $_POST['userEmail'], $_POST['password'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $userEmail = trim($_POST['userEmail']);
    // Phone number formatting
    $phoneNumberRaw = $_POST['phoneNumber'] ?? '';
    $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumberRaw); // Strip non-numeric characters
    if(strlen($phoneNumber) === 10) {
        // Format the phone number if it's the proper length
        $phoneNumber = substr($phoneNumber, 0, 3) . '-' . substr($phoneNumber, 3, 3) . '-' . substr($phoneNumber, 6);
    }
    $password = trim($_POST['password']);

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM user WHERE userEmail = ?");
    $stmt->execute([$userEmail]);
    if ($stmt->rowCount() > 0) {
        $error = 'An account with this email already exists.';
    } elseif (!isPasswordStrong($password)) {
        $error = 'Password must include at least one uppercase letter, one lowercase letter, and one number or special character.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Start the transaction
        $pdo->beginTransaction();
        try {
            // SQL to insert the new user with additional fields
            $stmt = $pdo->prepare("INSERT INTO user (firstName, lastName, userEmail, phoneNumber, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $userEmail, $phoneNumber, $passwordHash]);
            $userID = $pdo->lastInsertId(); // Get the last inserted ID for the user

            // Prepare security answers insertion
            $stmt = $pdo->prepare("INSERT INTO security_answers (userID, questionID, answerText) VALUES (?, ?, ?)");

            // Insert security answers
            for ($i = 1; $i <= 3; $i++) {
                $questionID = $_POST["question$i"];
                $answer = trim($_POST["answer$i"]);
                $stmt->execute([$userID, $questionID, $answer]);
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
    <?php if ($error): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>
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
            <input type="text" name="phoneNumber" id="phoneNumber" placeholder="123-456-7890">
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

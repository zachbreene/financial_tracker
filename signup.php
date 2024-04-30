<?php
// Start the session and output buffering
session_start();
ob_start();

// Include the database connection file
require_once 'includes/database-connection.php';

$error = '';  // Variable to store error messages
$success = '';  // Variable to store success message

// Function to check password strength
function isPasswordStrong($password) {
    return preg_match('/[A-Z]/', $password)      // at least one uppercase letter
        && preg_match('/[a-z]/', $password)      // at least one lowercase letter
        && (preg_match('/\d/', $password)        // at least one digit
            || preg_match('/\W/', $password));   // or special character
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['firstName'], $_POST['lastName'], $_POST['userEmail'], $_POST['phoneNumber'], $_POST['password'], $_POST['securityQuestion'], $_POST['securityAnswer'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $userEmail = trim($_POST['userEmail']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $password = trim($_POST['password']);
    $securityQuestion = trim($_POST['securityQuestion']);
    $securityAnswer = trim($_POST['securityAnswer']);

    // Validate email format
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format. <br>';
    } else {
        // Phone number formatting and validation
        if (!empty($phoneNumber)) {
            $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber); // Strip non-numeric characters
            if (strlen($phoneNumber) === 10) {
                // Format the phone number if it's the proper length
                $phoneNumber = substr($phoneNumber, 0, 3) . '-' . substr($phoneNumber, 3, 3) . '-' . substr($phoneNumber, 6);
            } else {
                $error = 'Invalid phone number format. <br>';
            }
        }

        // Check if email already exists
        if (!$error) {
            $stmt = $pdo->prepare("SELECT userID FROM user WHERE userEmail = ?");
            $stmt->execute([$userEmail]);
            if ($stmt->rowCount() > 0) {
                $error = 'Email already exists. <br>';
            }
        }

        if (!$error && !isPasswordStrong($password)) {
            $error = 'Password must include at least one uppercase letter, one lowercase letter, and one number or special character. <br><br>';
        }

        if (!$error) {
            // Proceed with the user registration if there are no errors
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into the database including security question and answer
            $sql = "INSERT INTO user (firstName, lastName, userEmail, phoneNumber, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $inserted = $stmt->execute([$firstName, $lastName, $userEmail, $phoneNumber, $passwordHash]);

            if ($inserted) {
                $userID = $pdo->lastInsertId();
                $sql = "INSERT INTO security_answers (userID, questionID, answerText) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userID, $securityQuestion, $securityAnswer]);

                $success = 'User registered successfully!';
                // Optionally redirect to login or dashboard page
                // header('Location: index.php');
            } else {
                $error = 'Failed to register user. Please try again. <br>';
            }
        }
    }
}

ob_end_flush();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="login-style.css">
</head>
<body>
    <div class="container">
        <h2>Sign Up</h2><br>
        <?php
        if ($error != '') {
            echo '<p style="color:red;">' . $error . '</p>';
        }
        if ($success != '') {
            echo '<p style="color:green;">' . $success . '</p>';
        }
        ?>
        <form action="signup.php" method="post">
            <div class="input-group">
                <input type="text" name="firstName" id="firstName" required placeholder="First Name">
                <label for="firstName">First Name:</label>
            </div>
            <div class="input-group">
                <input type="text" name="lastName" id="lastName" required placeholder="Last Name">
                <label for="lastName">Last Name:</label>
            </div>
            <div class="input-group">
                <input type="email" name="userEmail" id="userEmail" required placeholder="Email">
                <label for="userEmail">Email:</label>
            </div>
            <div class="input-group">
                <input type="text" name="phoneNumber" id="phoneNumber" placeholder="Phone Number">
                <label for="phoneNumber">Phone Number:</label>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder="Password">
                <label for="password">Password:</label>
            </div>
            <br>
            <div class="input-group">
                <label for="securityQuestion">Security Question:</label>
                <select name="securityQuestion" id="securityQuestion" required>
                    <option value="">Select a security question</option>
                    <option value="1">What is your mother's maiden name?</option>
                    <option value="2">What was the name of your first pet?</option>
                    <option value="3">What was the make of your first car?</option>
                    <option value="4">What is the name of the town where you were born?</option>
                    <option value="5">What is your favorite movie?</option>
                    <option value="6">What was the name of your elementary school?</option>
                    <option value="7">In what city did you meet your spouse/significant ...</option>
                    <option value="8">What is your favorite color?</option>
                    <option value="9">What street did you live on in third grade?</option>
                    <option value="10">What was your childhood nickname?</option>

                </select>

            </div>
            <br>
            <div class="input-group">
                <input type="text" name="securityAnswer" id="securityAnswer" required placeholder="Answer">
                <label for="securityAnswer">Your Answer:</label>
            </div>
            <div>
                <button type="submit" class="btn">Register</button>
            </div>
            <br>
            <div class="form-footer">
                <p>Already have an account? <a href="index.php" style="text-decoration: underline;">Login</a></p>
            </div>
        </form>
    </div>
    <script src="script.js"></script>
</body>
</html>

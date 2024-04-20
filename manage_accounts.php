<?php
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

// Include the database connection file
require_once 'includes/database-connection.php';

$userID = $_SESSION['userid'];

// Fetch all accounts associated with the logged-in user
$accountsStmt = $pdo->prepare("SELECT accountID, accountType, accountBalance FROM account WHERE userID = ?");
$accountsStmt->execute([$userID]);
$accounts = $accountsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts</title>
</head>
<body>
    <h1>Account Management</h1>
    <a href="dashboard.php">Back to Dashboard</a>
    <h2>Your Accounts</h2>
    <table>
        <thead>
            <tr>
                <th>Account Type</th>
                <th>Balance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $account): ?>
            <tr>
                <td><?= htmlspecialchars($account['accountType']) ?></td>
                <td>$<?= number_format(htmlspecialchars($account['accountBalance']), 2) ?></td>
                <td>
                    <a href="edit_account.php?accountID=<?= $account['accountID'] ?>">Edit</a> |
                    <a href="delete_account.php?accountID=<?= $account['accountID'] ?>" onclick="return confirm('Are you sure you want to delete this account?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><a href="add_account.php">Add New Account</a></p>
</body>
</html>
<?php
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

// Include the database connection file
require_once 'includes/database-connection.php';

$accountID = $_GET['accountID'] ?? null; // Get the accountID from the URL

// Fetch transactions for the selected account
$transactionsStmt = $pdo->prepare("SELECT transactionDescription, transactionAmount, transactionDate FROM transactions WHERE accountID = ? ORDER BY transactionDate DESC");
$transactionsStmt->execute([$accountID]);
$transactions = $transactionsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details</title>
</head>
<body>
    <h1>Account Transactions</h1>
    <a href="manage_accounts.php">Back to Accounts</a>
    <h2>Transactions</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?= htmlspecialchars($transaction['transactionDescription']) ?></td>
                <td>$<?= number_format(htmlspecialchars($transaction['transactionAmount']), 2) ?></td>
                <td><?= htmlspecialchars($transaction['transactionDate']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
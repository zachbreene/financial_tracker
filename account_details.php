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

// Handle POST requests for adding, updating, or searching transactions
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO transactions (transactionDescription, transactionAmount, transactionDate, transactionType, accountID) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $accountID]);
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}

if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE transactions SET transactionDescription = ?, transactionAmount = ?, transactionDate = ?, transactionType = ? WHERE transactionID = ?");
    $stmt->execute([$_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $_POST['transactionID']]);
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE transactionID = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}

$search = $_POST['search'] ?? '';
$sort = $_GET['sort'] ?? 'transactionDate';
$order = $_GET['order'] ?? 'DESC';

// Fetch transactions for the selected account including the transaction type
$transactionsStmt = $pdo->prepare("SELECT transactionID, transactionDescription, transactionAmount, transactionDate, transactionType FROM transactions WHERE accountID = ? AND (transactionDescription LIKE ? OR transactionAmount LIKE ?) ORDER BY $sort $order");
$transactionsStmt->execute([$accountID, '%' . $search . '%', '%' . $search . '%']);
$transactions = $transactionsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>Account Transactions</h1>
    <a href="manage_accounts.php">Back to Accounts</a>

    <!-- Search Form -->
    <form method="post" action="">
        <input type="text" name="search" placeholder="Search transactions...">
        <button type="submit">Search</button>
    </form>

    <!-- Transaction Form for Adding New Transactions -->
    <h2>Add New Transaction</h2>
    <form method="post" action="">
        <input type="text" name="description" placeholder="Description">
        <input type="number" name="amount" placeholder="Amount">
        <input type="date" name="date" placeholder="Date">
        <select name="type">
            <option value="Expense">Expense</option>
            <option value="Income">Income</option>
        </select>
        <button type="submit" name="add">Add Transaction</button>
    </form>

    <h2>Transactions</h2>
    <table>
        <thead>
            <tr>
                <th><a href="?accountID=<?= $accountID ?>&sort=transactionDate&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Date</a></th>
                <th><a href="?accountID=<?= $accountID ?>&sort=transactionAmount&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Amount</a></th>
                <th>Description</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?= htmlspecialchars($transaction['transactionDescription']) ?></td>
                <td>$<?= number_format(htmlspecialchars($transaction['transactionAmount']), 2) ?></td>
                <td><?= htmlspecialchars($transaction['transactionDate']) ?></td>
                <td><?= htmlspecialchars($transaction['transactionType']) ?></td>
                <td>
                    <a href="?accountID=<?= $accountID ?>&delete=<?= $transaction['transactionID'] ?>" onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php

ob_start(); // Start output buffering
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

// Include the database connection file
require_once 'includes/database-connection.php';

$accountID = $_GET['accountID'] ?? null; // Get the accountID from the URL
// Function to add a new transaction
function addTransaction($accountID, $description, $amount, $date, $type, $category) {
    global $pdo;

    $transactionAmount = ($type === 'Expense') ? -$amount : $amount;

    $stmt = $pdo->prepare("INSERT INTO transactions (transactionDescription, transactionAmount, transactionDate, transactionType, accountID, categoryID) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$description, $transactionAmount, $date, $type, $accountID, $category]);

    // Update account balance
    updateAccountBalance($accountID, $transactionAmount);

    // Redirect to account details page
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}

// Handle logout action
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Destroy the session and redirect to login page
    session_destroy();
    header('Location: index.php');
    exit();
}

// Function to update account balance
function updateAccountBalance($accountID, $transactionAmount) {
    global $pdo;

    // Fetch the initial balance from the account table
    $balanceStmt = $pdo->prepare("SELECT accountBalance FROM account WHERE accountID = ?");
    $balanceStmt->execute([$accountID]);
    $initialBalance = $balanceStmt->fetchColumn() ?: 0; // If null, default to 0

    // Calculate new balance
    $newBalance = $initialBalance + $transactionAmount;

    // Update the account balance in the account table
    $updateStmt = $pdo->prepare("UPDATE account SET accountBalance = ? WHERE accountID = ?");
    $updateStmt->execute([$newBalance, $accountID]);
}

// Function to delete a transaction
function deleteTransaction($accountID, $transactionID) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT transactionAmount, transactionType FROM transactions WHERE transactionID = ?");
    $stmt->execute([$transactionID]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $transactionAmount = $result['transactionAmount'];
    $transactionType = $result['transactionType'];

    // Update account balance
    updateAccountBalance($accountID, ($transactionType === 'Expense') ? abs($transactionAmount) : -$transactionAmount);

    // Delete the transaction
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE transactionID = ?");
    $stmt->execute([$transactionID]);

    // Redirect to account details page
    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}
// Fetch transactions for the selected account
function fetchTransactions($accountID, $categoryFilter = '', $amountMin = '', $amountMax = '', $dateStart = '', $dateEnd = '', $search = '', $sort = 'transactionDate', $order = 'DESC') {
    global $pdo;

    // Start building the query
    $queryParams = [$accountID]; // Begin with account ID
    $query = "SELECT t.transactionID, t.transactionDescription, t.transactionAmount, t.transactionDate, t.transactionType, c.categoryName 
              FROM transactions t 
              LEFT JOIN category c ON t.categoryID = c.categoryID 
              WHERE t.accountID = ?";

    // Add additional filters to the query
    if ($categoryFilter) {
        $query .= " AND c.categoryID = ?";
        $queryParams[] = $categoryFilter;
    }
    if ($amountMin !== '') {
        $query .= " AND t.transactionAmount >= ?";
        $queryParams[] = $amountMin;
    }
    if ($amountMax !== '') {
        $query .= " AND t.transactionAmount <= ?";
        $queryParams[] = $amountMax;
    }
    if ($dateStart) {
        $query .= " AND t.transactionDate >= ?";
        $queryParams[] = $dateStart;
    }
    if ($dateEnd) {
        $query .= " AND t.transactionDate <= ?";
        $queryParams[] = $dateEnd;
    }
    if ($search) {
        $query .= " AND (t.transactionDescription LIKE ? OR t.transactionAmount LIKE ?)";
        $queryParams[] = '%' . $search . '%';
        $queryParams[] = '%' . $search . '%';
    }

    // Add sorting to the query
    $query .= " ORDER BY $sort $order";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    
    return $stmt->fetchAll();
}

// Capture filter inputs from the POST request and apply them to the fetchTransactions call
$categoryFilter = $_POST['categoryFilter'] ?? '';
$amountMin = $_POST['amountMin'] ?? '';
$amountMax = $_POST['amountMax'] ?? '';
$dateStart = $_POST['dateStart'] ?? '';
$dateEnd = $_POST['dateEnd'] ?? '';

// Fetch the current balance from the account table
function fetchCurrentBalance($accountID) {
    global $pdo;

    $balanceStmt = $pdo->prepare("SELECT accountBalance FROM account WHERE accountID = ?");
    $balanceStmt->execute([$accountID]);
    return $balanceStmt->fetchColumn();
}

if (isset($_POST['add'])) {
    addTransaction($_GET['accountID'], $_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $_POST['category']);
}

if (isset($_GET['delete'])) {
    deleteTransaction($_GET['accountID'], $_GET['delete']);
}


// Handle POST requests for updating transactions
if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE transactions SET transactionDescription = ?, transactionAmount = ?, transactionDate = ?, transactionType = ?, categoryID = ? WHERE transactionID = ?");
    $stmt->execute([$_POST['description'], $_POST['amount'], $_POST['date'], $_POST['type'], $_POST['categoryID'], $_POST['transactionID']]);



    header('Location: account_details.php?accountID=' . $accountID);
    exit();
}



// Fetch the account details for the selected account
$search = $_POST['search'] ?? '';
$sort = $_GET['sort'] ?? 'transactionDate';
$order = $_GET['order'] ?? 'DESC';

$transactions = fetchTransactions($accountID, $categoryFilter, $amountMin, $amountMax, $dateStart, $dateEnd, $search, $sort, $order);
$currentBalance = fetchCurrentBalance($accountID);

ob_end_flush(); // End output buffering and flush all output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details</title>
    <link href="style.css" type="text/css" rel="stylesheet">
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
    <!--Fixed Sticky Navigation Bar-->
    <div tabindex="0" id="navcontainer">
            <ul class="navbar">
                <li class="navlist" id="dashboardHover"><a href="dashboard.php">Dashboard</a></li>
                <li class="navlist" id="accountHover"><a href="manage_accounts.php">Manage Accounts</a></li>
                <li class="navlist" id="budgetHover"><a href="manage_budgets.php">Manage Budgets</a></li>
                <li style="float:right" class="navlist" id="logoutHover"><a href="?action=logout">Logout</a></li>
            </ul>
    </div>
    <h1>Account Transactions</h1>
    <a href="manage_accounts.php">Back to Accounts</a>
    <br>
    <h2>Current Balance: $<?= number_format($currentBalance, 2) ?></h2>
    <br>
    <h2>Transactions Search</h2>
    <form method="post" action="">
        <input type="text" name="search" placeholder="Search transactions...">
        <button type="submit">Search</button>
    </form>
    <br>
    <h2>Add New Transaction</h2>
    <form method="post" action="">
        <input type="text" name="description" placeholder="Description">
        <input type="number" name="amount" placeholder="Amount">
        <input type="date" name="date" placeholder="Date">
        <select name="type">
            <option value="Expense">Expense</option>
            <option value="Income">Income</option>
        </select>
        <select name="category">
            <?php
            $categoriesStmt = $pdo->prepare("SELECT categoryID, categoryName FROM category");
            $categoriesStmt->execute();
            $categories = $categoriesStmt->fetchAll();
            foreach ($categories as $category) {
                echo '<option value="' . $category['categoryID'] . '">' . htmlspecialchars($category['categoryName']) . '</option>';
            }
            ?>
        </select>
        <br>
        <button type="submit" name="add">Add Transaction</button>
    </form>
    <br>
    <h2>Filter Transactions</h2>
    <form method="post" action="" id="filterForm">
        <label for="categoryFilter">Category:</label>
        <select name="categoryFilter" id="categoryFilter">
            <option value="">All Categories</option>
            <?php
            $categoriesStmt = $pdo->prepare("SELECT categoryID, categoryName FROM category");
            $categoriesStmt->execute();
            $categories = $categoriesStmt->fetchAll();
            foreach ($categories as $category) {
                echo '<option value="' . $category['categoryID'] . '">' . htmlspecialchars($category['categoryName']) . '</option>';
            }
            ?>
        </select>

        <label for="amountMin">Minimum Amount:</label>
        <input type="number" name="amountMin" id="amountMin" placeholder="Min Amount">

        <label for="amountMax">Maximum Amount:</label>
        <input type="number" name="amountMax" id="amountMax" placeholder="Max Amount">

        <label for="dateStart">Start Date:</label>
        <input type="date" name="dateStart" id="dateStart">

        <label for="dateEnd">End Date:</label>
        <input type="date" name="dateEnd" id="dateEnd">

        <button type="submit">Apply Filters</button>
        <button type="button" onclick="resetFilters()">Reset Filters</button>
    </form>
    <br>
    <h2>Transactions</h2>
    <table>
        <thead>
            <tr>
                <th><a href="?accountID=<?= $accountID ?>&sort=categoryName&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Category</a></th>
                <th><a href="?accountID=<?= $accountID ?>&sort=transactionAmount&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Amount</a></th>
                <th><a href="?accountID=<?= $accountID ?>&sort=transactionDate&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Date</a></th>
                <th><a href="?accountID=<?= $accountID ?>&sort=transactionType&order=<?= $order == 'DESC' ? 'ASC' : 'DESC' ?>">Type</a></th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?= htmlspecialchars($transaction['categoryName']) ?></td>
                <td>$<?= ($transaction['transactionType'] === 'Expense') ? number_format(abs(htmlspecialchars($transaction['transactionAmount'])), 2) : number_format(htmlspecialchars($transaction['transactionAmount']), 2) ?></td>
                <td><?= htmlspecialchars($transaction['transactionDate']) ?></td>
                <td><?= htmlspecialchars($transaction['transactionType']) ?></td>
                <td><?= htmlspecialchars($transaction['transactionDescription']) ?></td>
                <td>
                    <a href="?accountID=<?= $accountID ?>&delete=<?= $transaction['transactionID'] ?>" onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script>
    function resetFilters() {
        document.getElementById('filterForm').reset();
        window.location.href = window.location.pathname;
    }
    </script>
</body>
</html>
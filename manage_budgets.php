<?php
// Start the session
session_start();
ob_start();

// Include the database connection file
require_once 'includes/database-connection.php';

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

// Handle logout action
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Destroy the session and redirect to login page
    session_destroy();
    header('Location: index.php');
    exit();
}

$userID = $_SESSION['userid'];

// Fetch categories for the dropdown
$categoryStmt = $pdo->prepare("SELECT categoryID, categoryName FROM category");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll();

// Handle the form submission for a new budget
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_budget'])) {
    $categoryID = $_POST['categoryID'];
    $budgetLimit = $_POST['budgetLimit'];
    $budgetInterval = $_POST['budgetInterval'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];

    $stmt = $pdo->prepare("INSERT INTO budget (userID, categoryID, budgetLimit, budgetInterval, startDate, endDate) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userID, $categoryID, $budgetLimit, $budgetInterval, $startDate, $endDate]);

    header("Location: manage_budgets.php");
    exit();
}

// Retrieve existing budgets for the user
$budgetsStmt = $pdo->prepare("SELECT b.budgetID, c.categoryName, b.budgetLimit, b.budgetInterval, b.startDate, b.endDate FROM budget b INNER JOIN category c ON b.categoryID = c.categoryID WHERE b.userID = ?");
$budgetsStmt->execute([$userID]);
$budgets = $budgetsStmt->fetchAll();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Budgets</title>
    <link href="style.css" type="text/css" rel="stylesheet">
</head>
<body>
    <!--Fixed Sticky Navigation Bar-->
    <div tabindex="0" id="navcontainer">
            <ul class="navbar">
                <li class="navlist" id="dashboardHover"><a href="dashboard.php">Dashboard</a></li>
                <li class="navlist" id="accountHover"><a href="manage_accounts.php">Manage Accounts</a></li>
                <li class="navlist" id="budgetHover"><a class="active" href="manage_budgets.php">Manage Budgets</a></li>
                <li style="float:right" class="navlist" id="logoutHover"><a href="?action=logout">Logout</a></li>
            </ul>
    </div>
    <h1>Manage Budgets</h1>
    <br>
    <!-- Form to add a new budget -->
    <h2>Add New Budget</h2>
    <form action="manage_budgets.php" method="post">
        <label for="categoryID">Category:</label>
        <select id="categoryID" name="categoryID" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= htmlspecialchars($category['categoryID']) ?>"><?= htmlspecialchars($category['categoryName']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="budgetLimit">Budget Limit:</label>
        <input type="number" id="budgetLimit" name="budgetLimit" required>

        <label for="budgetInterval">Budget Interval:</label>
        <select id="budgetInterval" name="budgetInterval" required>
            <option value="Weekly">Weekly</option>
            <option value="Monthly">Monthly</option>
            <option value="Annual">Annual</option>
        </select>

        <label for="startDate">Start Date:</label>
        <input type="date" id="startDate" name="startDate" required>

        <label for="endDate">End Date:</label>
        <input type="date" id="endDate" name="endDate" required>

        <button type="submit" name="submit_budget">Add Budget</button>
    </form>
    <br>
    <!-- Table to list all budgets -->
    <h2>Your Budgets</h2>
    <br>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Budget Limit</th>
                <th>Budget Interval</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($budgets as $budget): ?>
            <tr>
                <td><?= htmlspecialchars($budget['categoryName']) ?></td>
                <td>$<?= htmlspecialchars(number_format($budget['budgetLimit'], 2)) ?></td>
                <td><?= htmlspecialchars($budget['budgetInterval']) ?></td>
                <td><?= htmlspecialchars($budget['startDate']) ?></td>
                <td><?= htmlspecialchars($budget['endDate']) ?></td>
                <td>
                    <!-- Edit link -->
                    <a href="edit_budget.php?budgetID=<?= $budget['budgetID'] ?>">Edit</a> |
                    <!-- Delete link -->
                    <a href="delete_budget.php?budgetID=<?= $budget['budgetID'] ?>" onclick="return confirm('Are you sure you want to delete this budget?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
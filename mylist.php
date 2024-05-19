<?php
session_start();

// Check if the user is not logged in
if (!isset($_SESSION['username'])) {
    // Redirect the user to the index.php page
    header("Location: index.php");
    exit(); // Stop further execution
}

include 'navbar.php';

// Assuming you have a database connection in 'db.php'
include 'db.php';

// Function to remove a book from the list table
function removeBook($bookId) {
    global $conn;

    $sql = "DELETE FROM list WHERE id = $bookId";

    if ($conn->query($sql) === TRUE) {
        // Send the success message to JavaScript
        echo "<script>showMessage('Book removed successfully');</script>";
    } else {
        // Send the error message to JavaScript
        echo "<script>showMessage('Error removing book: " . $conn->error . "');</script>";
    }
}

// Function to check if the list is empty
function isListEmpty() {
    global $conn;

    $result = $conn->query("SELECT COUNT(*) FROM list");
    $count = $result->fetch_assoc()['COUNT(*)'];

    return $count == 0;
}

// Check if a book removal request is made
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['remove'])) {
    $bookId = $_GET['remove'];
    removeBook($bookId);
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="mylist.css">
<script src="random.js"></script>
    <title>Book List</title>
</head>
<body>

<h2>Book List</h2>
<div class='container-table'>
<?php if (isListEmpty()): ?>
    <p>The book list is empty. Please add a book.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Publisher</th>
            <th>Rating</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <!-- Add your table rows here -->
    </table>
<?php endif; ?>
</div>

<script>
    // JavaScript function to display messages as popups
    function showMessage(message) {
        alert(message);
    }

    function removeBook(bookId) {
        if (confirm("Are you sure you want to remove this book?")) {
            window.location.href = `booklist.php?remove=${bookId}`;
        }
    }
</script>

</body>
</html>

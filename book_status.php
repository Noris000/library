<?php
// Start the session (if not already started)
session_start();

// Establish a connection to your current database (library)
$mysqli = new mysqli("localhost", "root", "", "library");

if ($mysqli->connect_error) {
    die("Connection to library database failed: " . $mysqli->connect_error);
}

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin_priv']) || $_SESSION['admin_priv'] != 1) {
    // Redirect to login page or display an error message
    header("Location: login.php");
    exit();
}

// Check if a book_id is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $book_id = $_GET['id'];

    // Retrieve the book title
    $get_book_title_sql = "SELECT title FROM books WHERE id = ?";
    $get_book_title_stmt = $mysqli->prepare($get_book_title_sql);

    if ($get_book_title_stmt) {
        $get_book_title_stmt->bind_param("i", $book_id);
        $get_book_title_stmt->execute();
        $get_book_title_result = $get_book_title_stmt->get_result();

        if ($get_book_title_result->num_rows === 1) {
            $book_title = $get_book_title_result->fetch_assoc()['title'];
        } else {
            echo "Book not found.";
            exit();
        }

        $get_book_title_stmt->close();
    } else {
        echo "Error: " . $mysqli->error;
        exit();
    }

    // Retrieve borrowing information for the book, including the username of the user who borrowed it
    $get_borrowings_sql = "SELECT user_id, username, borrow_date, due_date
                    FROM borrowed
                    INNER JOIN user ON borrowed.user_id = user.id
                    WHERE books_id = ?";
    $get_borrowings_stmt = $mysqli->prepare($get_borrowings_sql);

    if ($get_borrowings_stmt) {
        $get_borrowings_stmt->bind_param("i", $book_id);
        $get_borrowings_stmt->execute();
        $borrowings_result = $get_borrowings_stmt->get_result();
    } else {
        echo "Error: " . $mysqli->error;
        exit();
    }
} else {
    echo "Invalid book ID.";
    exit();
}

// Close the database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="book_status.css">
</head>
<body>
    <a href="dashboard.php" class="back-to-dashboard">Dashboard</a>
    <h1>Book Status</h1>
    <h2>Borrowing Information</h2>
    <table>
    <tr>
        <th>Book Title</th>
        <th>User ID</th>
        <th>Username</th>
        <th>Borrow Date</th>
        <th>Due Date</th>
    </tr>
    <?php
    while ($row = $borrowings_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $book_title . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['borrow_date'] . "</td>";
        echo "<td>" . $row['due_date'] . "</td>";
        echo "</tr>";
    }
    ?>
</table>
</body>
</html>
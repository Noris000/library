<?php
class DatabaseConnection {
    private $mysqli;

    public function __construct($host, $username, $password, $database) {
        $this->mysqli = new mysqli($host, $username, $password, $database);

        if ($this->mysqli->connect_error) {
            die("Connection failed: " . $this->mysqli->connect_error);
        }
    }

    public function sanitize($input) {
        return $this->mysqli->real_escape_string($input);
    }

    public function getBookById($bookId) {
        $getBookSql = "SELECT * FROM books WHERE id = ?";
        $getBookStmt = $this->mysqli->prepare($getBookSql);

        if ($getBookStmt) {
            $getBookStmt->bind_param("i", $bookId);
            $getBookStmt->execute();
            $bookResult = $getBookStmt->get_result();

            if ($bookResult->num_rows === 1) {
                return $bookResult->fetch_assoc();
            }
        }

        return null;
    }

    public function updateBook($bookId, $newTitle, $newAuthor, $newYear) {
        $updateSql = "UPDATE books SET title = ?, author = ?, year = ? WHERE id = ?";
        $updateStmt = $this->mysqli->prepare($updateSql);

        if ($updateStmt) {
            $updateStmt->bind_param("sssi", $newTitle, $newAuthor, $newYear, $bookId);
            if ($updateStmt->execute()) {
                return true;
            }
        }

        return false;
    }

    public function closeConnection() {
        $this->mysqli->close();
    }
}

// Create an instance of the DatabaseConnection class
$dbConnection = new DatabaseConnection('sql307.infinityfree.com', 'if0_34873008', 'r96Nydo0VbF', 'if0_34873008_library');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $bookId = $_GET['id'];
    $book = $dbConnection->getBookById($bookId);

    if (!$book) {
        echo "Book not found.";
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $newTitle = isset($_POST['title']) ? $dbConnection->sanitize($_POST['title']) : '';
        $newAuthor = isset($_POST['author']) ? $dbConnection->sanitize($_POST['author']) : '';
        $newYear = isset($_POST['year']) ? $dbConnection->sanitize($_POST['year']) : '';

        if ($dbConnection->updateBook($bookId, $newTitle, $newAuthor, $newYear)) {
            // Redirect back to the dashboard page after updating the book
            header("Location: dashboard.php?status=success");
            exit();
        } else {
            echo "Error updating book.";
        }
    }
} else {
    echo "Invalid book ID.";
    exit();
}

// Close the database connection
$dbConnection->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
    <link rel="stylesheet" href="edit_book.css">
</head>
<body>
    <h1>Edit Book</h1>
    <?php
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        if ($status === "success") {
            echo "<p>Book updated successfully!</p>";
        } else {
            echo "<p>Error updating book.</p>";
        }
    }
    ?>
    <form action="edit_book.php?id=<?php echo $book['id']; ?>" method="POST">
        <label for="title">Title:</label>
        <input type="text" name="title" value="<?php echo $book['title']; ?>" required><br><br>
        
        <label for="author">Author:</label>
        <input type="text" name="author" value="<?php echo $book['author']; ?>" required><br><br>
        
        <label for="year">Release Year:</label>
        <input type="number" name="year" value="<?php echo $book['year']; ?>" min="1800" max="2023" required><br><br>
        
        <input type="submit" value="Update Book">
    </form>
</body>
</html>
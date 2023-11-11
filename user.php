<?php
session_start();

class LibraryApp {
    private $mysqli;

    public function __construct() {
        $this->mysqli = new mysqli('sql307.infinityfree.com', 'if0_34873008', 'r96Nydo0VbF', 'if0_34873008_library');

        if ($this->mysqli->connect_error) {
            die("Connection failed: " . $this->mysqli->connect_error);
        }
    }

    public function getUserBorrowedBooks() {
        $user_id = $_SESSION['user_id'];
        $borrowed_books_result = null;

        $borrowed_books_sql = "SELECT user_borrow.borrow_date, user_borrow.due_date, books.title, books.id AS book_id
        FROM user_borrow
        INNER JOIN books ON user_borrow.books_id = books.id
        WHERE user_borrow.user_id = ?";
        $borrowed_books_stmt = $this->mysqli->prepare($borrowed_books_sql);

        if ($borrowed_books_stmt) {
            $borrowed_books_stmt->bind_param("i", $user_id);
            $borrowed_books_stmt->execute();
            if ($borrowed_books_stmt->error) {
                die("Execute error: " . $borrowed_books_stmt->error);
            }
            $borrowed_books_result = $borrowed_books_stmt->get_result();
            if ($this->mysqli->error) {
                die("Query error: " . $this->mysqli->error);
            }
        } else {
            die("Query preparation error: " . $this->mysqli->error);
        }

        return $borrowed_books_result;
    }

    public function handleBookReturn() {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["return_book"])) {
            $book_id_to_return = $_POST["return_book"];
    
            // Check if the user is logged in
            if (!isset($_SESSION["user_id"])) {
                die("You must be logged in to return books.");
            }
    
            // Get the user's ID from the session
            $user_id = $_SESSION["user_id"];
    
            // Delete the book record from the user_borrow table
            $delete_sql = "DELETE FROM user_borrow WHERE user_id = ? AND books_id = ?";
            $delete_stmt = $this->mysqli->prepare($delete_sql);
            
            if ($delete_stmt) {
                $delete_stmt->bind_param("ii", $user_id, $book_id_to_return);
                if (!$delete_stmt->execute()) {
                    die("Delete error: " . $delete_stmt->error);
                }
            } else {
                die("Query preparation error: " . $this->mysqli->error);
            }

    // echo "DELETE SQL: " . $delete_sql; // Debugging statement

            // Update the book's availability to 1 in the books table
            $update_sql = "UPDATE books SET available = 1 WHERE id = ?";
            $update_stmt = $this->mysqli->prepare($update_sql);
    
            if ($update_stmt) {
                $update_stmt->bind_param("i", $book_id_to_return);
                $update_stmt->execute();
            } else {
                die("Query preparation error: " . $this->mysqli->error);
            }
        }
    }

    public function closeConnection() {
        $this->mysqli->close();
    }
}

// Create an instance of the LibraryApp class
$libraryApp = new LibraryApp();

// Call methods to handle user actions
$libraryApp->handleBookReturn();

// Get the borrowed books for the user
$borrowed_books_result = $libraryApp->getUserBorrowedBooks();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="user.css">
</head>
<body>
<div class="background-container">
    <a href="index.php">
        <button id="mainButton">Main Button</button>
    </a>
    <h1>Profile</h1>

    <h2>Borrowed Books</h2>
    <table>
    <tr>
    <th class="sortable" data-column="title">
    <a href="?sort=title&order=<?php
    if (isset($_GET['sort']) && $_GET['sort'] === 'title') {
        if (isset($_GET['order']) && $_GET['order'] === 'asc') {
            echo 'desc';
        } else {
            echo 'asc';
        }
    } else {
        echo 'asc';
    }
    ?>">
        Title
        <?php
        if (isset($_GET['sort']) && $_GET['sort'] === 'title') {
            if (isset($_GET['order']) && $_GET['order'] === 'asc') {
                echo '(A-Z)';
            } else {
                echo '(Z-A)';
            }
        }
        ?>
    </a>
</th>
        <th class="sortable" data-column="borrow_date">
            <a href="?sort=borrow_date&order=<?php echo (isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc'; ?>">
                Borrow Date
                <?php
                if (isset($_GET['sort']) && $_GET['sort'] === 'borrow_date') {
                    echo (isset($_GET['order']) && $_GET['order'] === 'asc') ? '▲' : '▼';
                }
                ?>
            </a>
        </th>
        <th class="sortable" data-column="due_date">
            <a href="?sort=due_date&order=<?php echo (isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc'; ?>">
                Due Date
                <?php
                if (isset($_GET['sort']) && $_GET['sort'] === 'due_date') {
                    echo (isset($_GET['order']) && $_GET['order'] === 'asc') ? '▲' : '▼';
                }
                ?>
            </a>
        </th>
        <th>Action</th>
    </tr>
    <?php
    
    // var_dump($borrowed_books_result); // Debugging statement

    if ($borrowed_books_result->num_rows > 0) {
        while ($row = $borrowed_books_result->fetch_assoc()) {
            // Convert the due date to a DateTime object
            $due_date = DateTime::createFromFormat('Y-m-d', $row["due_date"]);
            
            // Calculate the number of days left
            $current_date = new DateTime();
            $interval = $current_date->diff($due_date);
            $days_left = $interval->format('%r%a');
    
            // Define a CSS class based on the number of days left
            $row_class = ($days_left == 1) ? 'red-row' : '';

            // echo "Days left: $days_left";

            echo '<tr class="' . $row_class . '">';
            echo "<td>" . $row["title"] . "</td>";
            echo "<td>" . $row["borrow_date"] . "</td>";
            echo "<td>" . $row["due_date"] . "</td>";
            echo '<td>
                <form method="POST" action="' . $_SERVER['PHP_SELF'] . '">
                <input type="hidden" name="return_book" value="' . $row['book_id'] . '">
                <button type="submit">Return</button>
                </form>
            </td>';
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No borrowed books found.</td></tr>";
    }    
    ?>
    </table>

    <?php
    // Close the database connection
    $libraryApp->closeConnection();
    ?>
</div>
<script>
    // Remove the sort parameters from the URL
    if (window.history.replaceState) {
        const urlWithoutSort = window.location.href.replace(/[?&]sort=[^&]*/g, '').replace(/[?&]order=[^&]*/g, '');
        window.history.replaceState({path: urlWithoutSort}, '', urlWithoutSort);
    }
</script>
</body>
</html>
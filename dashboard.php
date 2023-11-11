<?php
class LibraryApp
{
    private $mysqli;

    public function __construct()
    {
        $this->mysqli = new mysqli('sql307.infinityfree.com', 'if0_34873008', 'r96Nydo0VbF', 'if0_34873008_library');

        if ($this->mysqli->connect_error) {
            die("Connection failed: " . $this->mysqli->connect_error);
        }
        session_start();

        // Check if the user is logged in and is an admin
        if (!$this->isLoggedInAdmin()) {
            // Redirect to login page or display an error message
            header("Location: login.php");
            exit();
        }
    }

    private function isLoggedInAdmin()
    {
        return (isset($_SESSION['user_id']) && isset($_SESSION['admin_priv']) && $_SESSION['admin_priv'] == 1);
    }

    private function sanitize($input)
    {
        return $this->mysqli->real_escape_string($input);
    }

    public function handleBookDeletion()
    {
        if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
            $delete_id = $this->sanitize($_GET['delete']);
            $delete_sql = "DELETE FROM books WHERE id = ?";
            $delete_stmt = $this->mysqli->prepare($delete_sql);

            if ($delete_stmt) {
                $delete_stmt->bind_param("i", $delete_id);
                if ($delete_stmt->execute()) {
                    echo "Book deleted successfully!";
                } else {
                    echo "Error deleting book: " . $delete_stmt->error;
                }
                $delete_stmt->close();
            } else {
                echo "Error: " . $this->mysqli->error;
            }

            // Redirect to the same page to prevent deletion resubmission
            header("Location: dashboard.php");
            exit();
        }
    }

    public function addBook()
    {
        $title = isset($_POST['title']) ? $this->sanitize($_POST['title']) : '';
        $author = isset($_POST['author']) ? $this->sanitize($_POST['author']) : '';
        $year = isset($_POST['year']) ? $this->sanitize($_POST['year']) : '';

        // Check if the book already exists in the database
        $check_sql = "SELECT id FROM books WHERE title = ? AND author = ? AND year = ?";
        $check_stmt = $this->mysqli->prepare($check_sql);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if ($check_stmt) {
                $check_stmt->bind_param("sss", $title, $author, $year);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows > 0) {
                    echo "Book already exists in the database.";
                } else {
                    // Insert data into the 'books' table with the 'available' column
                    $insert_sql = "INSERT INTO books (title, author, year, available) VALUES (?, ?, ?, 1)";
                    $insert_stmt = $this->mysqli->prepare($insert_sql);

                    if ($insert_stmt) {
                        $insert_stmt->bind_param("sss", $title, $author, $year);
                        if ($insert_stmt->execute()) {
                            echo "Book added successfully!";
                        } else {
                            echo "Error: " . $insert_stmt->error;
                        }
                        $insert_stmt->close();
                    } else {
                        echo "Error: " . $this->mysqli->error;
                    }
                }

                $check_stmt->close();

                // Redirect to the same page to prevent form resubmission
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error: " . $this->mysqli->error;
            }
        }
    }

    public function getBooks()
{
    // Define default sorting order and column
    $sortColumn = "title";
    $sortDirection = "asc";

    // Check if a sorting request is made
    if (isset($_GET['sort']) && isset($_GET['order'])) {
        $sortColumn = $this->sanitize($_GET['sort']);
        $sortDirection = ($_GET['order'] === 'asc') ? 'asc' : 'desc';
    }

    // Define the triangle symbols for "Release Year"
    $yearHeader = ($sortColumn === 'year' && $sortDirection === 'asc') ? ' &#x25B2;' : ' &#x25BC;';

    $get_books_sql = "SELECT * FROM books ORDER BY $sortColumn $sortDirection";
    $get_books_result = $this->mysqli->query($get_books_sql);

    if ($get_books_result) {
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Dashboard</title>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
                <link rel="stylesheet" href="dashboard.css">
            </head>
            <body>
            <div class="main-button" style="display: flex; flex-direction: row-reverse;">
                    <a href="index.php">Back to Main</a>
                </div>
                <h1>Add a Book</h1>
                <form action="dashboard.php" method="POST">
                    <label for="title">Title:</label>
                    <input type="text" name="title" required><br><br>
                    
                    <label for="author">Author:</label>
                    <input type="text" name="author" required><br><br>
                    
                    <label for="year">Release Year:</label>
                    <input type="number" name="year" min="1800" max="2023" required><br><br>
                    
                    <input type="submit" value="Add Book">
                </form>

                <h2>Book List</h2>
                <table>
                <tr>
    <th class="sortable">
        <a href="?sort=title&order=<?php
            if (isset($_GET['sort']) && $_GET['sort'] === 'title' && isset($_GET['order']) && $_GET['order'] === 'asc') {
                echo 'desc';
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
    <th class="sortable">
        <a href="?sort=author&order=<?php
            if (isset($_GET['sort']) && $_GET['sort'] === 'author' && isset($_GET['order']) && $_GET['order'] === 'asc') {
                echo 'desc';
            } else {
                echo 'asc';
            }
        ?>">
            Author 
            <?php
            if (isset($_GET['sort']) && $_GET['sort'] === 'author') {
                if (isset($_GET['order']) && $_GET['order'] === 'asc') {
                    echo '(A-Z)';
                } else {
                    echo '(Z-A)';
                }
            }
            ?>
        </a>
    </th>
    <th class="sortable">
        <a href="?sort=year&order=<?php
            if (isset($_GET['sort']) && $_GET['sort'] === 'year' && isset($_GET['order']) && $_GET['order'] === 'asc') {
                echo 'desc';
            } else {
                echo 'asc';
            }
        ?>">
            Release Year
            <?php
            if (isset($_GET['sort']) && $_GET['sort'] === 'year') {
                if (isset($_GET['order']) && $_GET['order'] === 'asc') {
                    echo ' &#x25B2;'; // Triangle symbol for ascending order
                } else {
                    echo ' &#x25BC;'; // Triangle symbol for descending order
                }
            }
            ?>
        </a>
    </th>
    <th>Status</th>
    <th>Action</th>
</tr>

        <?php
        while ($row = $get_books_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['title'] . "</td>";
            echo "<td>" . $row['author'] . "</td>";
            echo "<td>" . $row['year'] . "</td>";
            echo "<td>" . ($row['available'] == 1 ? 'Available' : 'Borrowed') . "</td>";
            echo '<td><a href="edit_book.php?id=' . $row['id'] . '"><i class="fas fa-edit" style="font-size: 24px; color:black;"></i></a> | <a href="dashboard.php?delete=' . $row['id'] . '"><i class="fas fa-trash-alt" style="font-size: 24px; color:black;"></i></a> | <a href="book_status.php?id=' . $row['id'] . '"><i class="fas fa-info-circle" style="font-size: 24px; color:black;"></i></a></td>';
            echo "</tr>";
        }
        ?>
    </table>
    <script>
    // Remove the sort parameters from the URL
    if (window.history.replaceState) {
        const urlWithoutSort = window.location.href.replace(/[?&]sort=[^&]*/g, '').replace(/[?&]order=[^&]*/g, '');
        window.history.replaceState({path: urlWithoutSort}, '', urlWithoutSort);
    }
</script>
            </body>
            </html>
            <?php
        } else {
            echo "Error retrieving books: " . $this->mysqli->error;
        }
    }

    public function closeConnection()
    {
        $this->mysqli->close();
    }
}

// Create an instance of the LibraryApp class
$libraryApp = new LibraryApp();

// Handle book deletion
$libraryApp->handleBookDeletion();

// Add a book
$libraryApp->addBook();

// Get and display books
$libraryApp->getBooks();

// Close the database connection
$libraryApp->closeConnection();
?>
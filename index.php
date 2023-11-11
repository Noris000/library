<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<?php
// Start the session
session_start();

// Check if the user is logged in
$loggedIn = isset($_SESSION['user_id']); // Check if a user_id session variable exists

// Initialize user role
$userRole = ''; // Set it to an empty string initially

if ($loggedIn) {
    // Get the user's role from the 'admin_priv' session variable
    $adminPriv = $_SESSION['admin_priv'];

    if ($adminPriv == 1) { // Check as an integer
        // Admin user
        echo '<div class="header-container">
                <i id="cart-icon" class="fas fa-shopping-cart"></i>
                <a href="dashboard.php" class="role-button">Dashboard</a>
            </div>';
    } else {
        // Regular user
        echo '<div class="header-container">
                <i id="cart-icon" class="fas fa-shopping-cart"></i>
                <a href="user.php" class="role-button">Profile</a>
            </div>';
    }

    // Show the "Logout" button
    echo '<a href="logout.php" class="login-button">Logout</a>';
} else {
    // User is not logged in, show the "Sign In/Up" button
    echo '<a href="login.php" class="login-button">Sign In/Up</a>';
}
?>

<h1>Library</h1>

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
                        echo '<i class="fas fa-sort-up"></i>';
                    } else {
                        echo '<i class="fas fa-sort-down"></i>';
                    }
                }
                ?>
            </a>
        </th>
        <th>Action</th>
    </tr>
    <?php
    class LibraryManager {
        private $conn;

        public function __construct($host, $username, $password, $database) {
            $this->conn = new mysqli($host, $username, $password, $database);
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
        }

        public function getAvailableBooks($sortColumn, $sortOrder) {
            $orderBy = $sortColumn ?: 'title'; // Default sorting by title
            $orderDirection = ($sortOrder === 'asc') ? 'ASC' : 'DESC'; // Toggle the sorting order
            $query = "SELECT * FROM books WHERE available = 1 ORDER BY $orderBy $orderDirection";
            $result = $this->conn->query($query);
            $books = [];
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
            return $books;
        }
    }

    $sortColumn = isset($_GET['sort']) ? $_GET['sort'] : null;
    $sortOrder = (isset($_GET['order']) && ($_GET['order'] === 'asc' || $_GET['order'] === 'desc')) ? $_GET['order'] : 'asc'; // Default to ascending order
    $db = new LibraryManager('sql307.infinityfree.com', 'if0_34873008', 'r96Nydo0VbF', 'if0_34873008_library');
    $books = $db->getAvailableBooks($sortColumn, $sortOrder);

    foreach ($books as $book) {
        echo "<tr>";
        echo "<td id='title-" . $book['id'] . "'>" . $book['title'] . "</td>";
        echo "<td>" . $book['author'] . "</td>";
        echo "<td>" . $book['year'] . "</td>";
        // Add a button with an onclick event to add the book to the cart
        echo "<td><button onclick='addToCart(" . $book['id'] . ")'>Borrow</button></td>";
        echo "</tr>";
    }
    ?>
</table>

<!-- Cart icon -->
<i id="cart-icon" class="fas fa-shopping-cart"></i>

<!-- Cart popover -->
<div id="cart-popover">
    <div id="cart-items">No items in the cart.</div>
</div>
<script src="borrow_books.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
<script>
    // Remove the sort parameters from the URL
    if (window.history.replaceState) {
        const urlWithoutSort = window.location.href.replace(/[?&]sort=[^&]*/g, '').replace(/[?&]order=[^&]*/g, '');
        window.history.replaceState({path: urlWithoutSort}, '', urlWithoutSort);
    }
</script>
</body>
</html>
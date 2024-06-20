<?php
ob_start(); // Start output buffering
session_start();

// Check if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'navbar.php';
include 'db.php';

// Function to remove a book from the list table
function removeBook($bookId) {
    global $conn;

    // Sanitize the input to prevent SQL injection
    $bookId = $conn->real_escape_string($bookId);

    $sql = "DELETE FROM list WHERE id = $bookId";

    if ($conn->query($sql) === TRUE) {
        // Redirect back to mylist.php after successful removal
        header("Location: mylist.php");
        exit();
    } else {
        // Send the error message to JavaScript
        echo "<script>showMessage('Error removing book: " . $conn->error . "');</script>";
    }
}

// Function to update book details in the list table
function updateBookDetails($bookId, $rating, $status) {
    global $conn;

    // Sanitize the inputs to prevent SQL injection
    $bookId = $conn->real_escape_string($bookId);
    $rating = $conn->real_escape_string($rating);
    $status = $conn->real_escape_string($status);

    $sql = "UPDATE list SET rating = '$rating', status = '$status' WHERE id = $bookId";

    if ($conn->query($sql) === TRUE) {
        header("Location: mylist.php");
        exit();
    } else {
        // Send the error message to JavaScript
        echo "<script>showMessage('Error updating book: " . $conn->error . "');</script>";
    }
}

// Function to check if the list is empty
function isListEmpty() {
    global $conn;

    // Get the current logged-in user ID
    $user_id = $_SESSION['user_id'];
    
    // Sanitize the input to prevent SQL injection
    $user_id = $conn->real_escape_string($user_id);

    $result = $conn->query("SELECT COUNT(*) as count FROM list WHERE user_id = '$user_id'");
    $count = $result->fetch_assoc()['count'];

    return $count == 0;
}

// Function to fetch the book list
function fetchBookList() {
    global $conn;

    // Get the current logged-in user ID
    $user_id = $_SESSION['user_id'];
    
    // Sanitize the input to prevent SQL injection
    $user_id = $conn->real_escape_string($user_id);

    $sql = "SELECT id, user_id, book_id, title, author, rating, status FROM list WHERE user_id = '$user_id'";
    return $conn->query($sql);
}

// Check if a book removal request is made
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['remove'])) {
    $bookId = $_GET['remove'];
    removeBook($bookId);
}

// Check if the update form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_book'])) {
    $bookId = $_POST['book_id'];
    $rating = $_POST['score'];
    $status = $_POST['status'];
    updateBookDetails($bookId, $rating, $status);
}

ob_end_flush(); // Flush the output buffer and send the output
?>

<!DOCTYPE html>
<html style="background-color: peachpuff;">
<head>
<link rel="stylesheet" type="text/css" href="mylist.css">
<script src="random.js"></script>
    <title>Book List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/rowreorder/1.4.1/css/rowReorder.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/rowreorder/1.4.1/js/dataTables.rowReorder.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
</head>
<body style="background-color: peachpuff;">

<h2>Book List</h2>
<div class='container-table'>
<?php if (isListEmpty()): ?>
    <p>The book list is empty. Please add a book.</p>
<?php else: ?>
    <table id="books_list" class="display nowrap" style="width:100%">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <?php
        // Fetch the book list
        $result = fetchBookList();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['title']}</td>";
                echo "<td>{$row['author']}</td>";
                echo "<td>{$row['rating']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td>
                    <button style='background: #333; color: white; transition: background-color 0.3s;' onclick='showDeletePopup({$row['id']})' onmouseover=\"this.style.backgroundColor='#555'\" onmouseout=\"this.style.backgroundColor='#333'\">Remove</button>
                    <button style='background: #333; color: white; transition: background-color 0.3s;' onclick='openEditPopup({$row['id']}, \"{$row['rating']}\", \"{$row['status']}\")' onmouseover=\"this.style.backgroundColor='#555'\" onmouseout=\"this.style.backgroundColor='#333'\">Edit</button>
                    </td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
<?php endif; ?>
</div>

<!-- Pop-up Modal for Edit -->
<div class="overlay" id="overlay"></div>
<div class="popup" id="editPopup">
    <h2>Edit Book</h2>
    <form action="mylist.php" method="post">
        <input type="hidden" name="book_id" id="editBookId">
        <label for="status">Status:</label>
        <select id="editStatus" name="status">
            <option value="Reading">Reading</option>
            <option value="Completed">Completed</option>
            <option value="Plan To Read">Plan to Read</option>
            <option value="On-Hold">On-Hold</option>
            <option value="Dropped">Dropped</option>
        </select>
        <br><br>
        <label for="score">Score:</label>
        <select id="editScore" name="score">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
            <option value="7">7</option>
            <option value="8">8</option>
            <option value="9">9</option>
            <option value="10">10</option>
        </select>
        <br><br>
        <div class="button-container">
            <button type="button" onclick="closeEditPopup()">Cancel</button>
            <button type="submit" name="edit_book">Edit</button>
        </div>
    </form>
</div>

<script>
new DataTable('#books_list', {
    responsive: true,
    rowReorder: {
        selector: 'td:nth-child(2)'
    }
});

// JavaScript function to display messages as popups
function showMessage(message) {
    alert(message);
}

function showDeletePopup(bookId) {
    const overlay = document.getElementById('overlay');
    const popup = document.createElement('div');
    popup.classList.add('popup-text');
    
    popup.innerHTML = `
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this book?</p>
        <div class="button-container">
            <button onclick="confirmDeletion(${bookId})">Yes</button>
            <button onclick="closeDeletePopup()">No</button>
        </div>
    `;
    
    document.body.appendChild(popup);
    overlay.style.display = 'block';
    popup.style.display = 'block';
}

function closeDeletePopup() {
    const overlay = document.getElementById('overlay');
    const popup = document.querySelector('.popup-text');
    overlay.style.display = 'none';
    if (popup) {
        document.body.removeChild(popup);
    }
}

function confirmDeletion(bookId) {
    window.location.href = `mylist.php?remove=${bookId}`;
}

function openEditPopup(bookId, rating, status) {
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('editPopup').style.display = 'block';
    document.getElementById('editBookId').value = bookId;
    document.getElementById('editScore').value = rating;
    
    // Set the status dropdown to the current status
    const statusDropdown = document.getElementById('editStatus');
    for (let i = 0; i < statusDropdown.options.length; i++) {
        if (statusDropdown.options[i].value === status) {
            statusDropdown.selectedIndex = i;
            break;
        }
    }
}

function closeEditPopup() {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('editPopup').style.display = 'none';
}
</script>

</body>
</html>

<?php
// Include necessary files and start session
include 'navbar.php';
session_start();
include 'db.php';

// Function to fetch comments and replies recursively
function fetchComments($parent_id = NULL, $level = 0) {
    global $conn;

    // Sanitize the parent_id to prevent SQL injection
    $parent_id = $conn->real_escape_string($parent_id);

    // Retrieve comments with the given parent_id
    $sql = "SELECT * FROM review WHERE reply = '$parent_id'";
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='comment' style='margin-left: 2em;'>";
                if ($row['deleted'] == 1) {
                    echo "<p>Deleted Comment</p>";
                } else {
                    echo "<p>{$row['review']}</p>";
                }
                echo "<button class='reply-btn' onclick='toggleReplyBox({$row['id']})'>Reply</button>";
                if ($row['deleted'] != 1) {
                    echo "<form action='{$_SERVER["PHP_SELF"]}' method='post'>";
                    echo "<input type='hidden' name='delete_comment' value='{$row['id']}'>";
                    echo "<input type='submit' value='Delete'>";
                    echo "</form>";
                }
                echo "<div class='reply-box' id='replyBox_{$row['id']}'>";
                echo "<form action='{$_SERVER["PHP_SELF"]}' method='post'>";
                echo "<input type='hidden' name='book_id' value='{$row['book_id']}'>";
                echo "<input type='hidden' name='comment_id' value='{$row['id']}'>";
                echo "<textarea name='reply' rows='2' cols='30'></textarea><br>";
                echo "<input type='submit' value='Submit Reply'>";
                echo "</form>";
                echo "</div>";

                // Recursively fetch replies
                fetchComments($row['id'], $level + 1);

                echo "</div>";
            }
        }
    } else {
        echo "Error: " . $conn->error; // Output any SQL errors
    }
}

// Function to delete comment and its replies recursively
function deleteComments($comment_id) {
    global $conn;

    // Sanitize the comment_id to prevent SQL injection
    $comment_id = $conn->real_escape_string($comment_id);

    // Retrieve comments with the given parent_id
    $sql = "SELECT id FROM review WHERE reply = '$comment_id'";
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            // Recursively delete child comments
            while ($row = $result->fetch_assoc()) {
                deleteComments($row['id']);
            }
        }
    } else {
        echo "Error fetching comments: " . $conn->error;
        return;
    }

    // Delete the comment and its replies
    $sql_delete = "DELETE FROM review WHERE id = '$comment_id' OR reply = '$comment_id'";
    if ($conn->query($sql_delete) === TRUE) {
        echo "Comment(s) deleted successfully";
    } else {
        echo "Error deleting comment(s): " . $conn->error;
    }
}


// Check if the review form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        // Display a message or prompt the user to log in through the pop-up
        echo "<script>alert('Please login to submit a review.');</script>";
        exit(); // Stop further execution
    }

    // Process review submission
    $book_id = $_POST['book_id'] ?? '';
    $username = $_SESSION['username'];
    $review = $_POST['review'] ?? '';

    // Insert the review into the database
    $sql = "INSERT INTO review (book_id, username, review) VALUES ('$book_id', '$username', '$review')";
    if ($conn->query($sql) === TRUE) {
        echo "Review submitted successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Check if a reply is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply'])) {
    // Get the comment ID and other necessary information
    $comment_id = $_POST['comment_id'];
    $book_id = $_POST['book_id'];
    $username = $_SESSION['username'];
    $reply = $_POST['reply'];

    // Insert the reply into the database
    $sql = "INSERT INTO review (book_id, username, review, reply) VALUES ('$book_id', '$username', '$reply', '$comment_id')";
    if ($conn->query($sql) === TRUE) {
        echo "Reply submitted successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Details</title>
    <link rel="stylesheet" type="text/css" href="book.css">
    <style>
        .reply-box {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="container-book">
        <?php
            // Check if book details are set in the URL
            if(isset($_GET['title'])) {
                // Output book details
                echo "<h2>{$_GET['title']}</h2>";
                echo "<img src='{$_GET['cover']}' alt='Book Cover'>";
                echo "<p><strong>Author:</strong> {$_GET['author']}</p>";
                echo "<p><strong>Description:</strong> {$_GET['description']}</p>";
                echo "<p><strong>Publisher:</strong> {$_GET['publisher']}</p>";
                echo "<button id='addToList'>Add to List</button>";
                echo "<button onclick=\"window.location.href='" . (isset($_GET['url']) ? $_GET['url'] : '') . "'\">Buy Here</button>";
            }
            ?>
        </div>
        <div class="container-plot">
            <div class="book-plot">
                <!-- Add form for submitting reviews -->
                <h2>Write a Review</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="book_id" value="<?php echo isset($_GET['book_id']) ? $_GET['book_id'] : ''; ?>">
                    <?php
                    // Check if user is logged in
                    if (isset($_SESSION['username'])) {
                        // If logged in, display the review form
                        echo '<input type="hidden" name="username" value="' . $_SESSION['username'] . '">';
                        echo '<label for="review">Your Review:</label><br>';
                        echo '<textarea id="review" name="review" rows="4" cols="50"></textarea><br>';
                        echo '<input type="submit" value="Submit">';
                    } else {
                        // If not logged in, display a message or prompt the user to log in through the pop-up
                        echo '<p>Please login to submit a review.</p>';
                    }
                    ?>
                </form>

                <!-- Comments Section -->
                <?php
                // Fetch main comments
                fetchComments();
                ?>
            </div>
        </div>
    </div>

    <script>
        function toggleReplyBox(commentId) {
            var replyBox = document.getElementById('replyBox_' + commentId);
            if (replyBox.style.display === 'none') {
                replyBox.style.display = 'block';
            } else {
                replyBox.style.display = 'none';
            }
        }
    </script>
</body>
</html>

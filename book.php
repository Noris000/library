<?php
// Start output buffering to prevent any output before calling header
ob_start();

// Include necessary files and start session
include 'navbar.php';
session_start();
include 'db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to fetch comments and replies recursively
function fetchComments($book_id, $parent_id = NULL, $level = 0, $sort = 'newest') {
    global $conn;

    // Sanitize inputs to prevent SQL injection
    $book_id = $conn->real_escape_string($book_id);
    if ($parent_id !== NULL) {
        $parent_id = $conn->real_escape_string($parent_id);
    }

    // Retrieve comments for the specific book with the given parent_id, sorted by creation time
    $sortOrder = ($sort == 'newest') ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM review WHERE book_id = '$book_id' AND reply " . ($parent_id !== NULL ? "= '$parent_id'" : "IS NULL") . " ORDER BY created_at $sortOrder";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Display comments
        while ($row = $result->fetch_assoc()) {
            echo "<div class='comment' style='margin-left: " . ($level * 2) . "em;'>";
            if ($row['deleted'] == 1) {
                echo "<p>Deleted Comment</p>";
            } else {
                echo "<p><strong>{$row['username']}</strong> <em>{$row['created_at']}</em></p>";
                echo "<p>{$row['review']}</p>";
            }
            echo "<button class='reply-btn' onclick='toggleReplyBox({$row['id']})'>Reply</button>";

            // Check if there are replies before showing the toggle button
            $replyCheckSql = "SELECT COUNT(*) as reply_count FROM review WHERE reply = '{$row['id']}'";
            $replyCheckResult = $conn->query($replyCheckSql);
            $replyCount = $replyCheckResult->fetch_assoc()['reply_count'];

            if ($replyCount > 0) {
                echo "<button class='toggle-replies-btn' onclick='toggleReplies({$row['id']})'>Replies ({$replyCount})</button>";
            }

            // Display delete button only if the comment is not deleted and belongs to the logged-in user
            if ($row['deleted'] != 1 && isset($_SESSION['username']) && $_SESSION['username'] == $row['username']) {
                echo "<form action='{$_SERVER["PHP_SELF"]}' method='post'>";
                echo "<input type='hidden' name='delete_comment' value='{$row['id']}'>";
                echo "<input type='hidden' name='book_id' value='$book_id'>";
                echo "<input type='hidden' name='title' value='" . htmlspecialchars($_GET['title']) . "'>";
                echo "<input type='hidden' name='cover' value='" . htmlspecialchars($_GET['cover']) . "'>";
                echo "<input type='hidden' name='author' value='" . htmlspecialchars($_GET['author']) . "'>";
                echo "<input type='hidden' name='description' value='" . htmlspecialchars($_GET['description']) . "'>";
                echo "<input type='hidden' name='publisher' value='" . htmlspecialchars($_GET['publisher']) . "'>";
                echo "<input type='submit' value='Delete'>";
                echo "</form>";
            }

            // Display reply box if the user is logged in
            if (isset($_SESSION['username'])) {
                echo "<div class='reply-box' id='replyBox_{$row['id']}' style='display: none;'>";
                echo "<form action='{$_SERVER["PHP_SELF"]}' method='post'>";
                echo "<input type='hidden' name='book_id' value='$book_id'>";
                echo "<input type='hidden' name='parent_id' value='{$row['id']}'>";
                echo "<input type='hidden' name='title' value='" . htmlspecialchars($_GET['title']) . "'>";
                echo "<input type='hidden' name='cover' value='" . htmlspecialchars($_GET['cover']) . "'>";
                echo "<input type='hidden' name='author' value='" . htmlspecialchars($_GET['author']) . "'>";
                echo "<input type='hidden' name='description' value='" . htmlspecialchars($_GET['description']) . "'>";
                echo "<input type='hidden' name='publisher' value='" . htmlspecialchars($_GET['publisher']) . "'>";
                echo "<textarea name='reply' rows='2' cols='30'></textarea><br>";
                echo "<input type='submit' value='Reply'>";
                echo "</form>";
                echo "</div>";
            } else {
                echo "<div class='reply-box' id='replyBox_{$row['id']}' style='display: none;'>";
                echo "<p>Please login to reply.</p>";
                echo "</div>";
            }

            echo "<div class='replies-container' id='repliesContainer_{$row['id']}' style='display: none;'>";
            // Recursively fetch replies
            fetchComments($book_id, $row['id'], $level + 1, $sort);
            echo "</div>";

            echo "</div>";
        }
    } elseif ($parent_id === NULL) {
        // Display "No comments yet" message in italic if there are no main comments
        echo "<p><em>No comments yet.</em></p>";
    }
}

// Function to append query parameters to the URL
function appendQueryParams($url) {
    $queryParams = array(
        'title' => isset($_GET['title']) ? $_GET['title'] : '',
        'cover' => isset($_GET['cover']) ? $_GET['cover'] : '',
        'author' => isset($_GET['author']) ? $_GET['author'] : '',
        'description' => isset($_GET['description']) ? $_GET['description'] : '',
        'publisher' => isset($_GET['publisher']) ? $_GET['publisher'] : '',
        'book_id' => isset($_GET['book_id']) ? $_GET['book_id'] : ''
    );
    return $url . '?' . http_build_query($queryParams);
}

// Check if a comment deletion request is made
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_comment'])) {
    $comment_id = $_POST['delete_comment'];
    // Sanitize input to prevent SQL injection
    $comment_id = $conn->real_escape_string($comment_id);

    // Soft delete the comment by setting the 'deleted' flag to 1
    $sql = "UPDATE review SET deleted = 1 WHERE id = '$comment_id' AND username = '{$_SESSION['username']}'";
    if ($conn->query($sql) === TRUE) {
        // Redirect back to the same page to prevent form resubmission
        header("Location: " . appendQueryParams($_SERVER['PHP_SELF']));
        exit();
    } else {
        // Error updating comment
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Check if the review form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review'])) {
    // Process review submission
    $username = $_POST['username'];
    $book_id = $_POST['book_id'];
    $review = $_POST['review'];

    // Sanitize input to prevent SQL injection
    $username = $conn->real_escape_string($username);
    $book_id = $conn->real_escape_string($book_id);
    $review = $conn->real_escape_string($review);

    // Insert the comment into the database
    $sql = "INSERT INTO review (book_id, username, review) VALUES ('$book_id', '$username', '$review')";
    if ($conn->query($sql) === TRUE) {
        // Redirect back to the same page to prevent form resubmission
        header("Location: " . appendQueryParams($_SERVER['PHP_SELF']));
        exit();
    } else {
        // Error inserting comment
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Check if a reply is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply'])) {
    // Process reply submission
    $username = $_SESSION['username'];
    $book_id = $_POST['book_id'];
    $reply = $_POST['reply'];
    $parent_id = $_POST['parent_id'];

    // Sanitize input to prevent SQL injection
    $username = $conn->real_escape_string($username);
    $book_id = $conn->real_escape_string($book_id);
    $reply = $conn->real_escape_string($reply);
    $parent_id = $conn->real_escape_string($parent_id);

    // Insert the reply into the database
    $sql = "INSERT INTO review (book_id, username, review, reply) VALUES ('$book_id', '$username', '$reply', '$parent_id')";
    if ($conn->query($sql) === TRUE) {
        // Redirect back to the same page to prevent form resubmission
        header("Location: " . appendQueryParams($_SERVER['PHP_SELF']));
        exit();
    } else {
        // Error inserting reply
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Flush the output buffer and turn off output buffering
ob_end_flush();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Details</title>
    <link rel="stylesheet" type="text/css" href="book.css">
    <script src="random.js"></script>
</head>
<body>
    <div class="container">
        <div class="container-book">
        <?php
            // Check if book details are set in the URL
            if (isset($_GET['title'])) {
                // Output
                echo "<h2>{$_GET['title']}</h2>";
                echo "<img src='{$_GET['cover']}' alt='Book Cover'>";
                echo "<p><strong>Author:</strong> {$_GET['author']}</p>";
                echo "<p><strong>Description:</strong> {$_GET['description']}</p>";
                echo "<p><strong>Publisher:</strong> {$_GET['publisher']}</p>";
                echo "<div class='button-container'>";
                echo "<button id='addToList'>Add to List</button>";
                echo "<button onclick=\"window.location.href='" . (isset($_GET['url']) ? $_GET['url'] : '') . "'\">Buy Here</button>";
                echo "</div>";
            }
        ?>
        </div>
        <div class="container-plot">
            <div class="book-plot">
                <!-- Add form for submitting reviews -->
                <h2>Write a Review</h2>
                <form action="<?php echo appendQueryParams(htmlspecialchars($_SERVER["PHP_SELF"])); ?>" method="post">
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
                        // If not logged in, display a message
                        echo '<p>Please login to submit a review.</p>';
                    }
                    ?>
                </form>

                <!-- Comments Section -->
                <div class="comments-container">
                    <?php
                    // Fetch main comments
                    $book_id = isset($_GET['book_id']) ? $_GET['book_id'] : '';
                    fetchComments($book_id);
                    ?>
                </div>
            </div>
        </div>
    </div>

   <!-- Pop-up Modal for Add to List -->
<div class="overlay" id="overlay"></div>
<div class="popup" id="popup">
    <h2>Add to List</h2>
    <form id="addToListForm" action="add_to_list.php" method="post">
        <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($_GET['book_id']); ?>">
        <input type="hidden" name="title" value="<?php echo htmlspecialchars($_GET['title']); ?>">
        <input type="hidden" name="author" value="<?php echo htmlspecialchars($_GET['author']); ?>">
        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="reading">Reading</option>
            <option value="completed">Completed</option>
            <option value="plan_to_read">Plan to Read</option>
            <option value="on_hold">On-Hold</option>
            <option value="dropped">Dropped</option>
        </select>
        <br><br>
        <label for="score">Score:</label>
        <select id="score" name="score">
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
            <button type="button" onclick="closePopup()">Cancel</button>
            <button type="submit">Submit</button>
        </div>
    </form>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
    const addToListButton = document.getElementById('addToList');
    const overlay = document.getElementById('overlay');
    const popup = document.getElementById('popup');

    addToListButton.addEventListener('click', () => {
        // Check if the user is logged in
        const isLoggedIn = <?php echo isset($_SESSION['username']) ? 'true' : 'false'; ?>;

        if (isLoggedIn) {
            // Show the popup
            overlay.style.display = 'block';
            popup.style.display = 'block';
        } else {
            // Alert the user to log in
            alert('Please login to add the book to your list.');
        }
    });

    overlay.addEventListener('click', closePopup);
});

function closePopup() {
    const overlay = document.getElementById('overlay');
    const popup = document.getElementById('popup');

    overlay.style.display = 'none';
    popup.style.display = 'none';
}

        function toggleReplyBox(commentId) {
            var isLoggedIn = <?php echo isset($_SESSION['username']) ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                alert('Please login to reply.');
                return;
            }
            var replyBox = document.getElementById('replyBox_' + commentId);
            if (replyBox.style.display === 'none' || replyBox.style.display === '') {
                replyBox.style.display = 'block';
            } else {
                replyBox.style.display = 'none';
            }
        }

        function toggleReplies(commentId) {
            var repliesContainer = document.getElementById('repliesContainer_' + commentId);
            if (repliesContainer.style.display === 'none' || repliesContainer.style.display === '') {
                repliesContainer.style.display = 'block';
            } else {
                repliesContainer.style.display = 'none';
            }
        }
    </script>
</body>
</html>

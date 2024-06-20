<?php
// Start output buffering to prevent any output before calling header
ob_start();

// Include necessary files and start session
include 'navbar.php';
session_start();
include 'db.php';

// Function to retrieve the rating for a book from the database
function getBookRating($book_id) {
    global $conn;

    // Sanitize input to prevent SQL injection
    $book_id = $conn->real_escape_string($book_id);

    // Query to retrieve ratings for the book
    $sql = "SELECT rating FROM list WHERE book_id = '$book_id'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // If there are ratings, calculate the average
        $totalRating = 0;
        $ratingCount = $result->num_rows;
        while ($row = $result->fetch_assoc()) {
            $totalRating += $row['rating'];
        }
        $averageRating = $totalRating / $ratingCount;
        return $averageRating;
    } else {
        // If no ratings are found, return "No Rating Yet"
        return "No Rating Yet";
    }
}

// Function to check if the book is already in the user's list and return its status
function getUserBookStatus($user_id, $book_id) {
    global $conn;

    // Sanitize inputs to prevent SQL injection
    $user_id = $conn->real_escape_string($user_id);
    $book_id = $conn->real_escape_string($book_id);

    // Query to check if the book is in the user's list and fetch its status
    $sql = "SELECT status FROM list WHERE user_id = '$user_id' AND book_id = '$book_id'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['status'];
    } else {
        return null;
    }
}

// Function to check if a user exists in the database
function userExists($username) {
    global $conn;

    // Sanitize input to prevent SQL injection
    $username = $conn->real_escape_string($username);

    // Query to check if the user exists
    $sql = "SELECT COUNT(*) as count FROM user WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    } else {
        return false;
    }
}

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

    // Set the sorting order based on the provided sort parameter
    $order = $sort === 'oldest' ? 'ASC' : 'DESC';

    // Adjust SQL query to include sorting order
    $sql = "SELECT * FROM review WHERE book_id = '$book_id' AND reply " . ($parent_id !== NULL ? "= '$parent_id'" : "IS NULL") . " ORDER BY created_at $order";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Display comments
        while ($row = $result->fetch_assoc()) {
            echo "<div class='comment' style='margin-left: " . ($level * 2) . "em;'>";

            // Display comment details
            if ($row['deleted'] == 1 && userExists($row['username'])) {
                echo "<p class='comment-details'><strong>{$row['username']}</strong> <span class='timestamp'>{$row['created_at']}</span></p>";
                echo "<p class='comment-text'>Comment deleted</p>";
            } elseif ($row['deleted'] == 1) {
                echo "<p class='comment-details'><strong>User Deleted</strong> <span class='timestamp'>{$row['created_at']}</span></p>";
                echo "<p class='comment-text'>Comment deleted</p>";
            } else {
                echo "<p class='comment-details'><strong>{$row['username']}</strong> <span class='timestamp'>{$row['created_at']}</span></p>";
                echo "<p class='comment-text'>{$row['review']}</p>";
            
                // Create a container for buttons
                echo "<div class='button-container'>";
                // Display reply button only if the comment is not deleted
                echo "<button class='reply-btn' onclick='toggleReplyBox({$row['id']}, \"{$row['username']}\")'>Reply</button>";
            
                // Check if there are replies before showing the toggle button
                $replyCheckSql = "SELECT COUNT(*) as reply_count FROM review WHERE reply = '{$row['id']}'";
                $replyCheckResult = $conn->query($replyCheckSql);
                $replyCount = $replyCheckResult->fetch_assoc()['reply_count'];
            
                if ($replyCount > 0) {
                    echo "<button class='toggle-replies-btn' onclick='toggleReplies({$row['id']})'>Replies ({$replyCount})</button>";
                }
            
                // Display delete button only if the comment belongs to the logged-in user and is not deleted
                if ($row['deleted'] != 1 && isset($_SESSION['username']) && $_SESSION['username'] == $row['username']) {
                    echo "<form action='{$_SERVER["PHP_SELF"]}' method='post' class='delete-form'>";
                    echo "<input type='hidden' name='delete_comment' value='{$row['id']}'>";
                    echo "<input type='hidden' name='book_id' value='$book_id'>";
                    // Additional fields to preserve book details in the form submission
                    echo "<input type='hidden' name='title' value='" . htmlspecialchars($_GET['title']) . "'>";
                    echo "<input type='hidden' name='cover' value='" . htmlspecialchars($_GET['cover']) . "'>";
                    echo "<input type='hidden' name='author' value='" . htmlspecialchars($_GET['author']) . "'>";
                    echo "<input type='hidden' name='description' value='" . htmlspecialchars($_GET['description']) . "'>";
                    echo "<input type='hidden' name='publisher' value='" . htmlspecialchars($_GET['publisher']) . "'>";
                    echo "<input type='submit' class='delete-btn' value='Delete'>";
                    echo "</form>";
                }
            
                echo "</div>"; // Close button container div
            
                // Display options dropdown for edit/delete options
                if ($row['deleted'] != 1) {
                    echo "<div class='options-container'>";
                    echo "<div class='options-dropdown' id='optionsDropdown_{$row['id']}' style='display: none;'>";
                    echo "<button class='delete-btn'>Delete</button>";
                    echo "<button class='edit-btn'>Edit</button>";
                    echo "</div>";
                    echo "</div>";
                }
            
                // Display reply box if the user is logged in and the comment is not deleted
                if ($row['deleted'] != 1 && isset($_SESSION['username'])) {
                    echo "<div class='reply-box' id='replyBox_{$row['id']}' style='display: none;'>";
                    echo "<form action='{$_SERVER["PHP_SELF"]}' method='post'>";
                    echo "<input type='hidden' name='book_id' value='$book_id'>";
                    echo "<input type='hidden' name='parent_id' value='{$row['id']}'>";
                    // Additional fields to preserve book details in the form submission
                    echo "<input type='hidden' name='title' value='" . htmlspecialchars($_GET['title']) . "'>";
                    echo "<input type='hidden' name='cover' value='" . htmlspecialchars($_GET['cover']) . "'>";
                    echo "<input type='hidden' name='author' value='" . htmlspecialchars($_GET['author']) . "'>";
                    echo "<input type='hidden' name='description' value='" . htmlspecialchars($_GET['description']) . "'>";
                    echo "<input type='hidden' name='publisher' value='" . htmlspecialchars($_GET['publisher']) . "'>";
                    // Automatically include the name of the user whose comment is being replied to
                    echo "<textarea name='reply' rows='2' cols='30'>@{$row['username']}</textarea><br>";
                    echo "<input type='submit' class='submit-reply-btn' value='Reply'>";
                    echo "</form>";
                    echo "</div>";
                } elseif (!isset($_SESSION['username'])) {
                    // Display message to login for replying
                    echo "<div class='reply-box' id='replyBox_{$row['id']}' style='display: none;'>";
                    echo "<p>Please login to reply.</p>";
                    echo "</div>";
                }
            
                // Display container for replies
                echo "<div class='replies-container' id='repliesContainer_{$row['id']}' style='display: none;'>";
                // Recursively fetch replies with the same sorting order
                fetchComments($book_id, $row['id'], $level, $sort);
                echo "</div>"; // Close replies-container div
            }
            // Display container for replies
            echo "<div class='replies-container' id='repliesContainer_{$row['id']}' style='display: none;'>";
            
            // Recursively fetch replies with the same sorting order
            fetchComments($book_id, $row['id'], $level, $sort);
            
            echo "</div>"; // Close replies-container div

            echo "</div>"; // Close comment div
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

    // Check if the user exists in the database before deleting the comment
    $user_id = $_SESSION['user_id'];
    $checkUserSql = "SELECT * FROM user WHERE id = '$user_id'";
    $checkUserResult = $conn->query($checkUserSql);

    if ($checkUserResult->num_rows > 0) {
        // Soft delete the comment by setting the 'deleted' flag to 1
        $sql = "UPDATE review SET deleted = 1 WHERE id = '$comment_id' AND username = '{$_SESSION['username']}'";
        if ($conn->query($sql) === TRUE) {
            // Redirect back to the same page to prevent form resubmission
            $redirectUrl = appendQueryParams($_SERVER['PHP_SELF']);
            header("Location: " . $redirectUrl);
            exit();
        } else {
            // Error updating comment
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
}
}

// Function to sanitize input data
function sanitizeInput($data) {
    // Remove HTML tags and encode special characters
    return htmlspecialchars(strip_tags($data));
}

// Check if the review form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review'])) {
    // Process review submission
    $username = sanitizeInput($_POST['username']);
    $book_id = sanitizeInput($_POST['book_id']);
    $review = sanitizeInput($_POST['review']);

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

// Check if the add-to-list form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status']) && isset($_POST['score'])) {
    if (!isset($_SESSION['username'])) {

        echo "<p>Please login to add the book to your list.</p>";
    } else {
        $user_id = $_SESSION['user_id'];
        $book_id = $_POST['book_id'];
        $title = $_POST['title'];
        $author = $_POST['author'];
        $rating = $_POST['score'];
        $status = $_POST['status'];

        // Sanitize inputs to prevent SQL injection
        $user_id = $conn->real_escape_string($user_id);
        $book_id = $conn->real_escape_string($book_id);
        $title = $conn->real_escape_string($title);
        $author = $conn->real_escape_string($author);
        $rating = $conn->real_escape_string($rating);
        $status = $conn->real_escape_string($status);

        // Check if the book is already in the user's list
        $checkSql = "SELECT * FROM list WHERE user_id = '$user_id' AND book_id = '$book_id'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            // If the book is already in the list, update the status and rating
            $updateSql = "UPDATE list SET status = '$status', rating = '$rating', rated_at = CURRENT_TIMESTAMP WHERE user_id = '$user_id' AND book_id = '$book_id'";
            if ($conn->query($updateSql) === TRUE) {
                // Redirect to mylist.php after updating the book
                header("Location: mylist.php");
                exit();
            } else {
                // Error updating data
                echo "Error: " . $updateSql . "<br>" . $conn->error;
            }
        } else {
            // If the book is not in the list, insert it
            $insertSql = "INSERT INTO list (user_id, book_id, title, author, rating, status, rated_at) 
                          VALUES ('$user_id', '$book_id', '$title', '$author', '$rating', '$status', CURRENT_TIMESTAMP)";
            if ($conn->query($insertSql) === TRUE) {
                // Redirect to mylist.php after adding the book
                header("Location: mylist.php");
                exit();
            } else {
                // Error inserting data
                echo "Error: " . $insertSql . "<br>" . $conn->error;
            }
        }
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
                // Output book details
                echo "<h2>" . htmlspecialchars($_GET['title']) . "</h2>";
                echo "<img src='" . htmlspecialchars($_GET['cover']) . "' alt='Book Cover'>";
                echo "<p><strong>Author:</strong> " . htmlspecialchars($_GET['author']) . "</p>";
                echo "<p><strong>Description:</strong> " . htmlspecialchars($_GET['description']) . "</p>";
                echo "<p><strong>Publisher:</strong> " . htmlspecialchars($_GET['publisher']) . "</p>";

                // Call the getBookRating function to retrieve the rating
                $book_id = $_GET['book_id'];
                $bookRating = getBookRating($book_id);

                echo "<p><strong>Rating:</strong> " . $bookRating . "</p>";

                // Check if the book is already in the user's list to update the button text
                if (isset($_SESSION['username'])) {
                    $user_id = $_SESSION['user_id'];
                    $book_id = isset($_GET['book_id']) ? $_GET['book_id'] : '';

                    $checkSql = "SELECT status FROM list WHERE user_id = '$user_id' AND book_id = '$book_id'";
                    $checkResult = $conn->query($checkSql);

                    if ($checkResult->num_rows > 0) {
                        $row = $checkResult->fetch_assoc();
                        $buttonText = $row['status'];
                    } else {
                        $buttonText = "Add to List";
                    }
                } else {
                    $buttonText = "Add to List";
                }

                echo "<div class='button-container'>";
                echo "<button id='addToList' onclick='openPopup()'>" . htmlspecialchars($buttonText) . "</button>";
                echo "<button id='buyBook' onclick=\"window.location.href='" . (isset($_GET['url']) ? htmlspecialchars($_GET['url']) : '') . "'\">Buy Here</button>";
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
                echo '<input id="reviewBtn" type="submit" value="Submit">';
            } else {
                // If not logged in, display a message
                echo '<p>Please login to submit a review.</p>';
            }
            ?>
        </form>
        <div class="sort-btns">
            <form action="<?php echo appendQueryParams(htmlspecialchars($_SERVER["PHP_SELF"])); ?>" method="get">
                <?php
                // Preserve existing query parameters
                foreach ($_GET as $key => $value) {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
                ?>
                <button type="submit" name="sort" value="newest">Newest First</button>
                <button type="submit" name="sort" value="oldest">Oldest First</button>
            </form>
        </div>
        <!-- Comments Section -->
            <div class="comments-wrapper">
                <div class="comments-container">
                    <?php
                    // Fetch main comments
                    $book_id = isset($_GET['book_id']) ? $_GET['book_id'] : '';
                    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'oldest';
                    fetchComments($book_id, NULL, 0, $sort);
                    ?>
                </div>
            </div>
    </div>
</div>
    </div>

   <!-- Pop-up Modal for Add to List -->
   <div class="overlay" id="overlay"></div>
    <div class="popup" id="popup">
        <h2>Add to List</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query($_GET); ?>" method="post">
            <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($_GET['book_id']); ?>">
            <input type="hidden" name="title" value="<?php echo htmlspecialchars($_GET['title']); ?>">
            <input type="hidden" name="author" value="<?php echo htmlspecialchars($_GET['author']); ?>">
            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="Reading">Reading</option>
                <option value="Completed">Completed</option>
                <option value="Plan To Read">Plan to Read</option>
                <option value="On-Hold">On-Hold</option>
                <option value="Dropped">Dropped</option>
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
                <button class="cancel-btn" type="button" onclick="closePopup()">Cancel</button>
                <?php
        // Check if the book is already in the user's list to update the button text
        if (isset($_SESSION['username'])) {
            $user_id = $_SESSION['user_id'];
            $book_id = isset($_GET['book_id']) ? $_GET['book_id'] : '';

            $checkSql = "SELECT status FROM list WHERE user_id = '$user_id' AND book_id = '$book_id'";
            $checkResult = $conn->query($checkSql);

            if ($checkResult->num_rows > 0) {
                // If the book is already in the list, fetch the status
                $row = $checkResult->fetch_assoc();
                $userBookStatus = $row['status'];
                echo '<button class="add-btn" type="submit">Edit</button>';
            } else {
                // If the book is not in the list, set default values for status
                $userBookStatus = '';
                echo '<button class="add-btn" type="submit">Add</button>';
            }
        } else {
            // If the user is not logged in, set default values for status
            $userBookStatus = '';
            echo '<button class="add-btn" type="submit">Add</button>';
        }
            ?>
            </div>
        </form>
    </div>

    <script>

document.addEventListener('DOMContentLoaded', function() {
            var commentId = this.dataset.commentId;
            var optionsDropdown = document.getElementById('optionsDropdown_' + commentId);
            var deleteButton = optionsDropdown.querySelector('.delete-btn');
            deleteButton.style.display = (deleteButton.style.display === 'block') ? 'none' : 'block';
        });

        function openPopup() {
    var isLoggedIn = <?php echo isset($_SESSION['username']) ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        displayCustomAlert('Please login to add the book to your list.');
    } else {
        document.getElementById('overlay').style.display = 'block';
        document.getElementById('popup').style.display = 'block';
    }
}

function closePopup() {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('popup').style.display = 'none';
}
        function toggleReplyBox(commentId, username) {
            var isLoggedIn = <?php echo isset($_SESSION['username']) ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                displayCustomAlert('Please login to reply.');
                return;
            }
            
            var replyBox = document.getElementById('replyBox_' + commentId);
            if (replyBox.style.display === 'none' || replyBox.style.display === '') {
                replyBox.style.display = 'block';
                // Automatically include the name of the user whose comment is being replied to
                var replyTextarea = replyBox.querySelector('textarea');
                replyTextarea.value = '@' + username;
                replyTextarea.focus();
            } else {
                replyBox.style.display = 'none';
                document.getElementById('popup').style.display = 'none';
            }
        }

        function displayCustomAlert(message) {
    var alertContainer = document.createElement('div');
    alertContainer.classList.add('custom-alert');
    alertContainer.textContent = message;
    var containerDiv = document.querySelector('.container');
    document.body.insertBefore(alertContainer, containerDiv);
    setTimeout(function() {
        alertContainer.remove();
    }, 3000); // Remove alert after 3 seconds
}

document.addEventListener('DOMContentLoaded', function() {
    var deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var confirmed = confirm("Are you sure you want to delete this comment?");
            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
});

function toggleReplies(commentId) {
    var repliesContainer = document.getElementById('repliesContainer_' + commentId);
    repliesContainer.style.display = (repliesContainer.style.display === 'block') ? 'none' : 'block';
}
</script>
</body>
</html>
<?php

// Flush the output buffer and turn off output buffering
ob_end_flush();
?>
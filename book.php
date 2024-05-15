<?php

include 'navbar.php';

session_start();
include 'db.php';

// Check if the review form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        // Display a message or prompt the user to log in through the pop-up
        echo "<script>alert('Please login to submit a review.');</script>";
        exit(); // Stop further execution
    }

    // Process review submission
    $book_id = $_POST['book_id'];
    $username = $_SESSION['username'];
    $review = $_POST['review'];

    // Insert the review into the database
    $sql = "INSERT INTO reviews (book_id, username, review) VALUES ('$book_id', '$username', '$review')";
    if ($conn->query($sql) === TRUE) {
        echo "Review submitted successfully";
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
</head>
<body>
    <div class="container">
        <div class="container-book">
            <div class="book-info">
                <?php
                // Retrieve book information from query parameters
                $title = isset($_GET['title']) ? $_GET['title'] : '';
                $author = isset($_GET['author']) ? $_GET['author'] : '';
                $description = isset($_GET['description']) ? $_GET['description'] : '';
                $publisher = isset($_GET['publisher']) ? $_GET['publisher'] : '';
                $url = isset($_GET['url']) ? $_GET['url'] : '';
                $cover = isset($_GET['cover']) ? $_GET['cover'] : '';

                // Output book details
                echo "<h2>$title</h2>";
                echo "<img src='$cover' alt='Book Cover'>";
                echo "<p><strong>Author:</strong> $author</p>";
                echo "<p><strong>Description:</strong> $description</p>";
                echo "<p><strong>Publisher:</strong> $publisher</p>";
                echo "<a href='$url'>Buy the book</a>";
                ?>
            </div>
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
            </div>
        </div>
    </div>
</body>
</html>

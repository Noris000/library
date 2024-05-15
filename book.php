<?php

include 'navbar.php';

    session_start();
    include 'db.php';
    // Check if the review form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Check if user is logged in
        if (!isset($_SESSION['username'])) {
            // Redirect to login page if user is not logged in
            header("Location: login.php");
            exit();
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
                $pages = isset($_GET['pages']) ? $_GET['pages'] : '';
                $genres = isset($_GET['genres']) ? $_GET['genres'] : '';
                $cover = isset($_GET['cover']) ? $_GET['cover'] : '';
                $url = isset($_GET['url']) ? $_GET['url'] : '';
                $plot = isset($_GET['plot']) ? $_GET['plot'] : '';
                $rating = isset($_GET['rating']) ? $_GET['rating'] : '';

                // Output book details
                echo "<img src='$cover' alt='Book Cover'>";
                echo "<h2>$title</h2>";
                echo "<p>$author</p>";
                echo "<p>Pages: $pages</p>";
                echo "<p>Genres: $genres</p>";
                echo "<a href='$url'>Buy the book</a>";
                ?>
            </div>
        </div>
        <div class="container-plot">
            <div class="book-plot">
                <h2>Plot</h2>
                <?php echo "<p>$plot</p>"; ?>
                <h2>Reviews</h2>

                <?php
                $book_id = isset($_GET['book_id']) ? $_GET['book_id'] : '';
                $sql = "SELECT * FROM reviews WHERE book_id = '$book_id'";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        echo "<p><strong>" . $row["username"]. "</strong>: " . $row["review"]. "</p>";
                    }
                } else {
                    echo "No reviews yet.";
                }
                ?>

                <!-- Add form for submitting reviews -->
                <h2>Write a Review</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                    <?php
                    // Check if user is logged in
                    if (isset($_SESSION['username'])) {
                        // If logged in, display the review form
                        echo '<input type="hidden" name="username" value="' . $_SESSION['username'] . '">';
                        echo '<label for="review">Your Review:</label><br>';
                        echo '<textarea id="review" name="review" rows="4" cols="50"></textarea><br>';
                        echo '<input type="submit" value="Submit">';
                    } else {
                        // If not logged in, display a message and a link to the login page
                        echo '<p>Please <a href="login.php">login</a> to submit a review.</p>';
                    }
                    ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

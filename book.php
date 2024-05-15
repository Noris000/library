<?php
// Include necessary files and start session
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
        <div class="container-book" id="bookContainer">
            <?php
            // Check if book details are set in the URL
            if(isset($_GET['title'])) {
                // Output book details
                echo "<h2>{$_GET['title']}</h2>";
                echo "<img src='{$_GET['cover']}' alt='Book Cover'>";
                echo "<p><strong>Author:</strong> {$_GET['author']}</p>";
                echo "<p><strong>Description:</strong> {$_GET['description']}</p>";
                echo "<p><strong>Publisher:</strong> {$_GET['publisher']}</p>";
                echo "<a class='buy' href='{$_GET['url']}'>Buy the book</a>";
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
            </div>
        </div>
    </div>

    <script>
        async function fetchRandomBook() {
            const url = 'https://all-books-api.p.rapidapi.com/getBooks';
            const options = {
                method: 'GET',
                headers: {
                    'X-RapidAPI-Key': '7ce71ed5a7msha4203985a4cda5bp174b0ajsnb14b7bd8b868',
                    'X-RapidAPI-Host': 'all-books-api.p.rapidapi.com'
                }
            };

            try {
                const response = await fetch(url, options);
                const books = await response.json();
                const randomBook = books[Math.floor(Math.random() * books.length)]; // Get a random book from the array
                displayRandomBook(randomBook);
            } catch (error) {
                console.error(error);
            }
        }

        function displayRandomBook(book) {
            // Clear previous book info
            const bookContainer = document.getElementById('bookContainer');
            bookContainer.innerHTML = '';

            // Construct the HTML for the book details
            const bookInfo = `
                <h2>${book.bookTitle}</h2>
                <img src="${book.bookImage}" alt="Book Cover">
                <p><strong>Author:</strong> ${book.bookAuthor}</p>
                <p><strong>Description:</strong> ${book.bookDescription}</p>
                <p><strong>Publisher:</strong> ${book.bookPublisher}</p>
                <a class='buy' href="${book.amazonBookUrl}">Buy the book</a>
            `;

            // Append the book info to the container
            bookContainer.innerHTML = bookInfo;
        }
    </script>
</body>
</html>
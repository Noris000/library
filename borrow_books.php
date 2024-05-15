<?php
session_start(); // Start the session at the very beginning of the file

// Read the raw JSON data from the request body
$jsonData = file_get_contents('php://input');

// Decode the JSON data into an associative array
if ($data = json_decode($jsonData, true)) {
    error_log("POST request received for borrowing books.");

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Return an error message or redirect to the login page
        echo "You must be logged in to borrow books.";
        error_log("Code reached this point.");
        exit;
    }

    // Get the user ID from the session
    $userId = $_SESSION['user_id'];

    // Get the list of checked books from the client
    $checkedBooks = $data['checked_books']; // Assuming this is an array of book IDs

    if (!empty($checkedBooks)) {
        // Connect to your database
        $conn = new mysqli('localhost', 'root', '', 'library');

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Define the borrow date and due date (14 days from the current date)
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+14 days'));

        // Initialize a variable to track any errors (corrected to false)
        $error = false;

        // Begin a database transaction (to ensure data consistency)
        $conn->begin_transaction();

        // Loop through the checked books and record the borrowing information
        foreach ($checkedBooks as $bookId) {
            // Insert the borrowing record into the 'borrow' table
            $insertBorrowed = $conn->prepare("INSERT INTO borrowed (books_id, user_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
            $insertBorrowed->bind_param("iiss", $bookId, $userId, $borrowDate, $dueDate);

            if (!$insertBorrowed->execute()) {
                // Handle the error (e.g., log it, set the error flag)
                $error = true;
                break; // Exit the loop on error
            }

            // Insert the borrowing record into the 'user_borrow' table
            $insertUserBorrow = $conn->prepare("INSERT INTO user_borrow (books_id, user_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
            $insertUserBorrow->bind_param("iiss", $bookId,$userId, $borrowDate, $dueDate);

            if (!$insertUserBorrow->execute()) {
                // Handle the error (e.g., log it, set the error flag)
                $error = true;
                break; // Exit the loop on error
            }

            // Update the book's availability status in your 'books' table
            $updateBook = $conn->prepare("UPDATE books SET available = 0 WHERE id = ?");
            $updateBook->bind_param("i", $bookId);

            if (!$updateBook->execute()) {
                // Handle the error (e.g., log it, set the error flag)
                $error = true;
                break; // Exit the loop on error
            }
        }

        // Commit or rollback the database transaction based on whether there was an error
        if (!$error) {
            $conn->commit();
            echo "Books borrowed successfully!";
        } else {
            $conn->rollback();
            echo "An error occurred while borrowing books.";
        }

        // Close the database connection
        $conn->close();
    } else {
        echo "No books selected for borrowing.";
    }
} else {
    echo "Invalid request.";
}
?>
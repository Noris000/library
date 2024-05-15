<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['return_book'])) {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "You must be logged in to return books.";
        // You can also redirect the user to the login page
        exit;
    }

    $book_id_to_return = $_POST['return_book'];

    // Perform the book return operation here (update the database, set return_date, etc.)
    
    // After the operation is completed, you can redirect the user to a success page or back to the user profile page.
    header("Location: user_profile.php");
    exit;
}
?>
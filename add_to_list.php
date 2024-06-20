<?php
session_start();
include 'db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['username'])) {
    $status = $_POST['status'];
    $rating = $_POST['score'];
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];

    // Sanitize inputs
    $status = $conn->real_escape_string($status);
    $rating = $conn->real_escape_string($rating);
    $book_id = $conn->real_escape_string($book_id);
    $title = $conn->real_escape_string($title);
    $author = $conn->real_escape_string($author);

    // Get user_id from the session
    $username = $_SESSION['username'];
    $user_id_sql = "SELECT id FROM user WHERE username = '$username'";
    $user_id_result = $conn->query($user_id_sql);
    if ($user_id_result->num_rows > 0) {
        $user_id_row = $user_id_result->fetch_assoc();
        $user_id = $user_id_row['id'];

        // Insert into list table
        $insert_sql = "INSERT INTO list (user_id, book_id, title, author, rating, status) 
                       VALUES ('$user_id', '$book_id', '$title', '$author', '$rating', '$status')";
        if ($conn->query($insert_sql) === TRUE) {
            header("Location: mylist.php");
            exit();
        } else {
            echo "Error: " . $insert_sql . "<br>" . $conn->error;
        }
    } else {
        echo "Error: User not found.";
    }
} else {
    echo "Error: Unauthorized access or invalid request.";
}
?>

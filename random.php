<?php
// // Include necessary files
// include 'db.php';

// // Define function to fetch a random book's ISBN
// function getRandomBookISBN() {
//     global $conn; // Assuming $conn is your database connection object

//     // Query to select a random book's ISBN
//     $sql = "SELECT isbn FROM books ORDER BY RAND() LIMIT 1";

//     // Execute the query
//     $result = $conn->query($sql);

//     // Check if query executed successfully and fetched a result
//     if ($result && $result->num_rows > 0) {
//         // Fetch the ISBN from the result
//         $row = $result->fetch_assoc();
//         return $row['isbn'];
//     } else {
//         // Handle the case when no book is found
//         return null;
//     }
// }

// // Fetch a random book's ISBN
// $randomISBN = getRandomBookISBN();

// // Redirect to book.php with the random book's ISBN as a query parameter
// if ($randomISBN) {
//     header("Location: book.php?book_id=$randomISBN");
//     exit();
// } else {
//     // Handle the case when no book is found
//     echo "No random book found.";
// }
?>
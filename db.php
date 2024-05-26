<?php
    $host = "sql307.infinityfree.com";
    $username = "if0_34873008";
    $password = "r96Nydo0VbF";
    $dbname = "if0_34873008_library";

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
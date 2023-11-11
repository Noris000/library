<?php
// Start the session
session_start();

// Clear any existing output
ob_clean();

// Disable caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Unset and destroy the session to log the user out
session_unset();
session_destroy();

// Redirect the user to index.php after logout
header("Location: index.php");
exit;
?>
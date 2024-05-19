<?php
include 'db.php';

// Start session
session_start();

// Initialize variable for login message
$loginMessage = "";
$registrationMessage = "";

// Initialize variables for error messages
$emailError = "";
$usernameError = "";
$passwordError = "";

// Check if login welcome message has been shown and the current page is the home page
if (!isset($_SESSION['login_welcome_message_shown']) && isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) == 'index.php') {
    // Set session variable to indicate that the message has been shown
    $_SESSION['login_welcome_message_shown'] = true;

    // Set login welcome message
    $loginMessage = "Welcome " . $_SESSION['username'] . "!";
}

// Handle user login only if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and bind SQL statement
    $stmt = $conn->prepare("SELECT id, username, password FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);

    // Execute SQL statement
    $stmt->execute();

    // Store result
    $stmt->store_result();

    // Check if username exists
    if ($stmt->num_rows == 1) {
        // Bind result variables
        $stmt->bind_result($id, $db_username, $db_password);

        // Fetch values
        if ($stmt->fetch()) {
            // Verify password
            if (password_verify($password, $db_password)) {
                // Password is correct, start a new session

                // Store data in session variables
                $_SESSION["user_id"] = $id;
                $_SESSION["username"] = $db_username;

                // Set login success session variable
                $_SESSION["login_success"] = true;

                // Set the welcome message flag to false to ensure it shows after the redirection
                unset($_SESSION['login_welcome_message_shown']);

                // Redirect user to index page
                header("location: index.php");
                exit();
            }
        }
    }

    // Close statement
    $stmt->close();
}

// Handle user registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_submit'])) {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate username
    if (strlen($username) < 6 || strlen($username) > 18) {
        $usernameError = "Username must be between 6 and 18 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9._]+$/', $username)) {
        $usernameError = "Username can only contain letters, numbers, dots, and underscores.";
    } elseif (preg_match('/^[._]|[_\.]$/', $username)) {
        $usernameError = "Username cannot start or end with dots or underscores.";
    }

    // Validate password
    if (strlen($password) < 8 || strlen($password) > 20) {
        $passwordError = "Password must be between 8 and 20 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $passwordError = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }

    // Validate email address
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Invalid email address format.";
    }

    // If there are no errors, proceed with registration
    if (empty($emailError) && empty($usernameError) && empty($passwordError)) {
        // Hash password for security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and bind SQL statement
        $stmt = $conn->prepare("INSERT INTO user (email, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $username, $hashedPassword);

        // Execute SQL statement
        if ($stmt->execute()) {
            // Registration successful, set session variables for the newly registered user
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;

            // Close statement
            $stmt->close();

            // Set the welcome message flag to false to ensure it shows after the redirection
            unset($_SESSION['login_welcome_message_shown']);

            // Redirect user to index page
            header("location: index.php");
            exit();
        } else {
            // Registration failed, set error message
            $registrationMessage = "Error occurred during registration. Please try again.";
        }
    }

    // Close statement
    $stmt->close();
}

// Display the welcome message if it's set and the current page is the home page
if (!empty($loginMessage) && basename($_SERVER['PHP_SELF']) == 'index.php') {
    echo '<script>showPopupMessage("' . $loginMessage . '");</script>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="navbar.css">
    <script>
        // Function to show pop-up message
        function showPopupMessage(message) {
            var popup = document.createElement('div');
            popup.innerHTML = message;
            popup.className = "welcome-popup";
            document.body.appendChild(popup);

            // Hide the popup after 3 seconds (adjust the time as needed)
            setTimeout(function() {
                popup.style.display = 'none';
            }, 3000); // 3000 milliseconds = 3 seconds
        }
    </script>
</head>
<body>

<div class="menu-icon" onclick="toggleMenu()">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
        <path fill="white" d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
    </svg>
</div>

<nav class="navbar">
    <ul class="nav-list">
        <li class="nav-item"><a href="index.php">Home</a></li>
        <li class="nav-item"><a onclick="fetchRandomBook()">Random Book</a></li>
        <li class="nav-item"><a onclick="redirectToReviews()">Reviews</a></li>
        <li class="nav-item"><a onclick="redirectToRatings()">Ratings</a></li>
        <?php if (isset($_SESSION['user_id'])) : ?>
            <li class="nav-item"><a href="mylist.php">My List</a></li>
            <li class="nav-item"><a href="account.php">My Account</a></li>
        <?php endif; ?>
    </ul>
    <div class="user-profile">
        <?php if (isset($_SESSION['user_id'])) : ?>
            <a href="logout.php" class="a">Logout</a>
        <?php else : ?>
            <a href="#" onclick="showLoginModal()" class="a">Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Login Modal -->
<div id="loginModal" class="modal-container">
    <div class="modal-content">
        <span class="close" onclick="closeModal('loginModal')">&times;</span>
        <h2 class="text-center">Login</h2>
        <?php if (!empty($loginMessage)) : ?>
            <p class="text-center" style="color: red;"><?= $loginMessage ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
                <!-- No suggestion icon -->
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <!-- No suggestion icon -->
            </div>
            <div class="form-group">
                <input type="submit" name="login_submit" class="btn btn-primary btn-block" value="Login">
            </div>
        </form>
        <p class="text-center pointer" onclick="showRegisterModal()">Don't have an account? <span style="color: blue; text-decoration: underline; cursor: pointer;">Register here</span></p>
    </div>
</div>

<!-- Register Modal -->
<div id="registerModal" class="modal-container" <?php if($errors) echo 'style="display: block;"'; ?>>
    <div class="modal-content">
        <span class="close" onclick="closeModal('registerModal')">&times;</span>
        <h2 class="text-center">Register</h2>
        <?php if (!empty($registrationMessage)) : ?>
            <p class="text-center" style="color: red;"><?= $registrationMessage ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
                <!-- Email icon -->
                <span class="icon" onclick="showEmailSuggestions()">🛈</span>
                <!-- Suggestions container -->
                <div class="suggestions-container" id="emailSuggestions">
                    <ul>
                        <li>Should be in a valid email format (e.g., example@example.com).</li>
                    </ul>
                </div>
                <span class="error-message"><?php if (!empty($emailError)) echo $emailError; ?></span>
            </div>
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
                <!-- Username icon -->
                <span class="icon" onclick="showUsernameSuggestions()">🛈</span>
                <!-- Suggestions container -->
                <div class="suggestions-container" id="usernameSuggestions">
                    <ul>
                        <li>Length requirements: min 6 max 18.</li>
                        <li>No special symbols (e.g., !, @, #, $, %, etc.).</li>
                        <li>No spaces at the beginning or end.</li>
                        <li>Allow certain special characters like dots (.) or underscores (_), but not at the beginning or end.</li>
                    </ul>
                </div>
                <span class="error-message"><?php if (!empty($usernameError)) echo $usernameError; ?></span>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <!-- Password icon -->
                <span class="icon" onclick="showPasswordSuggestions()">🛈</span>
                <!-- Suggestions container -->
                <div class="suggestions-container" id="passwordSuggestions">
                    <ul>
                        <li>Minimum length at least 8 characters.</li>
                        <li>Must contain uppercase and lowercase letters, numbers, and special characters.</li>
                        <li>Maximum length 20.</li>
                    </ul>
                </div>
                <span class="error-message"><?php if (!empty($passwordError)) echo $passwordError; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" name="register_submit" class="btn btn-primary btn-block" value="Register">
            </div>
        </form>
        <p class="text-center pointer margin-top" onclick="showLoginModal()">Already have an account? <span style="color: blue; text-decoration: underline; cursor: pointer;">Login here</span></p>
    </div>
</div>

<script>
    function toggleMenu() {
        var navList = document.querySelector('.nav-list');
        navList.classList.toggle('active');
    }

    async function fetchRandomBook() {
        const options = {
            method: 'GET',
            url: 'https://books-api7.p.rapidapi.com/books/get/random/',
            headers: {
                'X-RapidAPI-Key': '7ce71ed5a7msha4203985a4cda5bp174b0ajsnb14b7bd8b868',
                'X-RapidAPI-Host': 'books-api7.p.rapidapi.com'
            }
        };

        try {
            const response = await axios.request(options);
            console.log(response.data);

            window.location.href = 'book.php';
        } catch (error) {
            console.error(error);
        }
    }

    function redirectToReviews() {
        window.location.href = "reviews.php";
    }

    function redirectToRatings() {
        window.location.href = "ratings.php";
    }

    function showLoginModal() {
        // Hide register modal if it's open
        document.getElementById('registerModal').style.display = 'none';
        // Show login modal
        document.getElementById('loginModal').style.display = 'block';
    }

    function showRegisterModal() {
        // Hide login modal if it's open
        document.getElementById('loginModal').style.display = 'none';
        // Show register modal
        document.getElementById('registerModal').style.display = 'block';
    }

    function closeModal(modalId) {
        // Hide the modal with the given ID
        document.getElementById(modalId).style.display = 'none';
    }

    function showUsernameSuggestions() {
        // Toggle the display of username suggestions container
        var suggestionsContainer = document.getElementById('usernameSuggestions');
        suggestionsContainer.classList.toggle('active');
    }

    function showPasswordSuggestions() {
        // Toggle the display of password suggestions container
        var suggestionsContainer = document.getElementById('passwordSuggestions');
        suggestionsContainer.classList.toggle('active');
    }

    function showEmailSuggestions() {
        // Toggle the display of email suggestions container
        var suggestionsContainer = document.getElementById('emailSuggestions');
        suggestionsContainer.classList.toggle('active');
    }

    // Call the function to show the welcome message popup if there is a login message
    <?php if (!empty($loginMessage)) : ?>
    showPopupMessage("<?= $loginMessage ?>");
    <?php endif; ?>
</script>

</body>
</html>

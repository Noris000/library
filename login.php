<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login & Register</title>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<link href="login.css" rel="stylesheet">
</head>
<body>
    <div class="container mx-auto"> <!-- Added mx-auto class here -->
        <div id="loginForm">
            <h2 class="text-center">Login</h2>
            <form action="" method="post">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary btn-block" value="Login">
                </div>
            </form>
            <p class="text-center pointer" onclick="showRegisterForm()">Don't have an account? Register here</p>
        </div>

        <div id="registerForm" class="hidden">
            <h2 class="text-center">Register</h2>
            <form action="" method="post">
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary btn-block" value="Register">
                </div>
            </form>
            <p class="text-center pointer margin-top" onclick="showLoginForm()">Already have an account? Login here</p>
        </div>
    </div>

    <script>
        function showRegisterForm() {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.remove('hidden');
        }

        function showLoginForm() {
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('loginForm').classList.remove('hidden');
        }
    </script>

    <?php
    // Include database connection
    include 'db.php';

    // Backend PHP code for registration and login
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Registration
        if (isset($_POST['email']) && isset($_POST['username']) && isset($_POST['password'])) {
            $email = $_POST['email'];
            $username = $_POST['username'];
            $password = $_POST['password'];

            // Hash the password before saving to the database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user data into the database
            $stmt = $conn->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $username, $hashed_password);

            if ($stmt->execute()) {
                echo "<script>alert('Registration successful!')</script>";
            } else {
                echo "<script>alert('Registration failed!')</script>";
            }

            $stmt->close();
        }

        // Login
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            // Query the database for the user
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $hashed_password);
                $stmt->fetch();

                // Verify password
                if (password_verify($password, $hashed_password)) {
                    echo "<script>alert('Login successful!')</script>";
                } else {
                    echo "<script>alert('Incorrect password!')</script>";
                }
            } else {
                echo "<script>alert('User not found!')</script>";
            }

            $stmt->close();
        }
    }

    // Close database connection
    $conn->close();
    ?>
</body>
</html>
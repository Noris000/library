<?php

class Authentication {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        session_start();
        // error_reporting(E_ALL);
        // ini_set('display_errors', 1);
    }

    public function register() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] === "register") {
            $email = $_POST["email"];
            $username = $_POST["username"];

           // Validate the username for special characters
        if (!$this->isValidUsername($username)) {
            echo '<script>document.getElementById("usernameError").innerHTML = "Error: Invalid username. Special characters are not allowed.";</script>';
            return;
        }

        // Validate the email format
        if (!$this->isValidEmail($email)) {
            echo '<script>document.getElementById("emailError").innerHTML = "Error: Invalid email format.";</script>';
            return;
        }

        // Validate the password (you can customize the validation rules)
        if (!$this->isValidPassword($password)) {
            echo '<script>document.getElementById("passwordError").innerHTML = "Error: Invalid password. It should be at least 8 characters long.";</script>';
            return;
        }

            $password = password_hash($_POST["password"], PASSWORD_BCRYPT);

            $sql = "INSERT INTO user (email, username, password, admin_priv) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $admin_priv = 0;
            $stmt->bind_param("sssi", $email, $username, $password, $admin_priv);

            if ($stmt->execute()) {
                $this->redirectUser();
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }

        // Add a function to validate the username
        private function isValidUsername($username) {
            return preg_match('/^[a-zA-Z0-9]+$/', $username);
        }
    
        // Add a function to validate the email format
        private function isValidEmail($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }
    
        // Add a function to validate the password (customize as needed)
        private function isValidPassword($password) {
            return strlen($password) >= 8;
        }

    public function login() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] === "login") {
            $email = $_POST["email"];
            $password = $_POST["password"];
        
            $sql = "SELECT id, email, password, admin_priv FROM user WHERE email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
        
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row["password"])) {
                    // Successful login
                    $_SESSION["user_id"] = $row["id"];
                    $_SESSION["email"] = $row["email"];
                    $_SESSION["admin_priv"] = $row["admin_priv"];
                    $this->redirectUser();
                } else {
                    echo "Password verification failed. Stored hash: " . $row["password"] . "<br>";
                    echo "Input hash: " . password_hash($password, PASSWORD_BCRYPT);
                    $this->redirectWithError();
                }
            } else {
                $this->redirectWithError();
            }
        }
    }

    private function redirectUser() {
        if ($_SESSION["admin_priv"] == 1) {
            header("Location: dashboard.php");
            exit;
        } else {
            header("Location: index.php");
            exit;
        }
    }

    private function redirectWithError() {
        header("Location: login.php?login_error=1");
        exit;
    }
}


include("db.php");
$auth = new Authentication($conn);
$auth->register();
$auth->login();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In/Up</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="container" id="container">
    <div class="form-container sign-up-container">
        <form action="login.php" method="post">
            <input type="hidden" name="action" value="register">
            <h1>Create Account</h1>
            <input type="text" name="username" placeholder="Username" />
            <div class="error" id="usernameError"></div>
            <input type="email" name="email" placeholder="Email" />
            <div class="error" id="emailError"></div>
            <input type="password" name="password" placeholder="Password" />
            <div class="error" id="passwordError"></div>
            <button>Sign Up</button>
        </form>
    </div>
    <div class="form-container sign-in-container">
        <form action="login.php" method="post">
            <input type="hidden" name="action" value="login">
            <h1>Sign in</h1>
            <input type="email" name="email" placeholder="Email" />
            <div class="error" id="loginEmailError"></div>
            <input type="password" name="password" placeholder="Password" />
            <div class="error" id="loginPasswordError"></div>
            <button name="submit">Sign In</button>
        </form>
    </div>
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <button class="ghost" id="signIn">Sign In</button>
            </div>
            <div class="overlay-panel overlay-right">
                <button class="ghost" id="signUp">Sign Up</button>
            </div>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
<?php
session_start();
ob_start();
include 'db.php';

// Check if the user is not logged in, then redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include navbar
include 'navbar.php';

$userID = $_SESSION['user_id'];

$sql = "SELECT * FROM user WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found. UserID: $userID";
    exit();
}

$stmt->close();

$errors = []; // Array to store validation errors

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle form submission based on the update section
    $updateSection = $_POST["update_section"];

    if ($updateSection == "update_email") {
        // Handle email update
        $newEmail = $_POST["new_email"];

        // Validate email
        if (empty($newEmail)) {
            $errors["email"] = "Email cannot be empty";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Should be in a valid email format (e.g., example@example.com)";
        } else {
            // Update email in the database
            $updateSql = "UPDATE user SET email = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $newEmail, $userID);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Email updated";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $errors["db_error"] = "Error updating email: " . $stmt->error;
            }
            $stmt->close();
        }

    } elseif ($updateSection == "update_password") {
        // Handle password update
        $currentPassword = $_POST["current_password"];
        $newPassword = $_POST["new_password"];
        $confirmPassword = $_POST["confirm_password"];

        // Validate current password
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors["password"] = "Password fields cannot be empty";
        } elseif (!password_verify($currentPassword, $user["password"])) {
            $errors["password"] = "Current password is incorrect";
        } elseif (strlen($newPassword) < 8) {
            $errors["new_password"] = "Password must be at least 8 characters long and only contain letters and numbers.";
        } elseif ($newPassword !== $confirmPassword) {
            $errors["confirm_password"] = "Passwords do not match";
        } else {
            // Hash the new password and update it in the database
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE user SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $hashedPassword, $userID);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Password updated";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $errors["db_error"] = "Error updating password: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Reload user data if there are no validation errors
    if (empty($errors)) {
        $sql = "SELECT id, email, username FROM user WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
}

$conn->close();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="account.css">
    <script src="random.js"></script>
</head>
<body>
<div class="container-info">
    <!-- User profile update form -->
    <form action="" method="POST">
        <div class="user-info">
            <h2 class="section-title">Current Information</h2>
            <!-- Display current user information -->
            <p><strong>Username: </strong><?php echo htmlspecialchars($user["username"]); ?></p>
            <p><strong>Email: </strong><?php echo htmlspecialchars($user["email"]); ?></p>
        </div>
    </div>
    <div class="container-info">
        <!-- Update Email Section -->
        <div class="update-email">
            <h2 class="section-title">Update Email</h2>
            <label for="new_email"><strong>New Email:</strong></label>
            <input type="text" id="new_email" name="new_email" value="<?php echo isset($_POST['new_email']) ? htmlspecialchars($_POST['new_email']) : ''; ?>">
            <?php if (isset($errors["email"])): ?>
                <p class="error"><?php echo $errors["email"]; ?></p>
            <?php endif; ?>
            <button type="submit" name="update_section" value="update_email">Update Email</button>
        </div>

        <!-- Password change section -->
        <div class="password-change">
            <h2 class="section-title">Password Change</h2>
            <label for="current_password"><strong>Current Password:</strong></label>
            <input type="password" id="current_password" name="current_password">
            <?php if (isset($errors["password"])): ?>
                <p class="error"><?php echo $errors["password"]; ?></p>
            <?php endif; ?>

            <label for="new_password"><strong>New Password:</strong></label>
            <input type="password" id="new_password" name="new_password">
            <?php if (isset($errors["new_password"])): ?>
                <p class="error"><?php echo $errors["new_password"]; ?></p>
            <?php endif; ?>

            <label for="confirm_password"><strong>Confirm New Password:</strong></label>
            <input type="password" id="confirm_password" name="confirm_password">
            <?php if (isset($errors["confirm_password"])): ?>
                <p class="error"><?php echo $errors["confirm_password"]; ?></p>
            <?php endif; ?>
            <button type="submit" name="update_section" value="update_password">Update Password</button>
        </div>
    </div>
</form>

<?php if (isset($_SESSION['success_message'])): ?>
    <div id="popup" class="popup show">
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
    <script>
        setTimeout(function () {
            document.getElementById('popup').classList.remove('show');
        }, 3000);
    </script>
<?php endif; ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const buttons = document.querySelectorAll("button[name='update_section']");
        buttons.forEach(button => {
            button.addEventListener("click", function () {
                document.getElementById("update_section").value = this.value;
            });
        });
    });
</script>
</body>
</html>

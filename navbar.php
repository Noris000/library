<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<nav class="navbar">
    <div class="nav-item"><a href="index.php">Home</a></div>

    <?php
    if (isset($_SESSION['user_id'])) {
        $adminPriv = $_SESSION['admin_priv'];
        echo '<div class="nav-item"><a href="' . ($adminPriv == 1 ? 'dashboard.php' : 'user.php') . '">My Account</a></div>';
    }
    ?>

    <div class="nav-item user-profile">
        <?php
        if (isset($_SESSION['user_id'])) {
            echo $_SESSION['user_id'];
            echo '<a href="logout.php">Logout</a>';
        } else {
            echo '<a href="login.php">Login</a>';
        }
        ?>
    </div>
</nav>

</body>
<style>
    body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

.navbar {
    background-color: #333;
    overflow: hidden;
}

.nav-item {
    float: left;
    display: block;
    color: white;
    text-align: center;
    padding: 14px 16px;
    text-decoration: none;
}

.nav-item a {
    color: white;
    text-decoration: none;
}

.nav-item:hover {
    background-color: #ddd;
    color: black;
}

.user-profile {
    float: right;
}

.user-profile a {
    display: inline-block;
    color: white;
    text-align: center;
    padding: 14px 16px;
    text-decoration: none;
}

.user-profile a:hover {
    background-color: #ddd;
    color: black;
}
</style>
</html>
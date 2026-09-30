<?php

session_start();

include "config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT User_ID, Name, Email, Password
         FROM users
         WHERE Email = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user["Password"])) {

            $_SESSION["user_id"] = $user["User_ID"];
            $_SESSION["user_name"] = $user["Name"];
            $_SESSION["user_email"] = $user["Email"];

            header("Location: dashboard.php");
            exit();

        } else {

            $message = "Invalid email or password.";

        }

    } else {

        $message = "Invalid email or password.";

    }

    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - ParkEase</title>

    <link rel="stylesheet"
          href="css/style.css">

</head>

<body>

    <div class="auth-container">

        <div class="auth-card">

            <div class="logo auth-logo">
                Park<span>Ease</span>
            </div>

            <h2>Welcome Back</h2>

            <p class="auth-subtitle">
                Login to your ParkEase account
            </p>


            <?php if (isset($_GET["registered"])): ?>

                <div class="success-message">
                    Account created successfully.
                    Please login.
                </div>

            <?php endif; ?>


            <?php if ($message != ""): ?>

                <div class="error-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <form method="POST"
                  action="login.php">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >


                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >


                <button
                    type="submit"
                    class="auth-button">

                    Login

                </button>

            </form>


            <p class="auth-footer">

                Don't have an account?

                <a href="register.php">
                    Register
                </a>

            </p>
<div class="admin-divider">
    <span>OR</span>
</div>

           <a href="admin-login.php" class="admin-login-link">
    Admin Login
</a>
        </div>

    </div>

</body>

</html>
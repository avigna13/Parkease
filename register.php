<?php

include "config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Check password
    if ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        // Check if email already exists
        $check = mysqli_prepare(
            $conn,
            "SELECT User_ID FROM users WHERE Email = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $email
        );

        mysqli_stmt_execute($check);

        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "Email is already registered.";

        } else {

            // Hash password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert user
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (Name, Email, Phone, Password, Address)
                VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $name,
                $email,
                $phone,
                $hashed_password,
                $address
            );

            if (mysqli_stmt_execute($stmt)) {

                header("Location: login.php?registered=1");
                exit();

            } else {

                $message = "Registration failed. Please try again.";

            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Register - ParkEase</title>

    <link rel="stylesheet"
          href="css/style.css">

</head>

<body>

    <div class="auth-container">

        <div class="auth-card">

            <div class="logo auth-logo">
                Park<span>Ease</span>
            </div>

            <h2>Create Account</h2>

            <p class="auth-subtitle">
                Register to start using ParkEase
            </p>


            <?php if ($message != ""): ?>

                <div class="error-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <form method="POST"
                  action="register.php">

                <label>Name</label>

                <input
                    type="text"
                    name="name"
                    placeholder="Enter your name"
                    required
                >


                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >


                <label>Phone</label>

                <input
                    type="text"
                    name="phone"
                    placeholder="Enter your phone number"
                    required
                >


                <label>Address</label>

                <input
                    type="text"
                    name="address"
                    placeholder="Enter your address"
                >


                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >


                <label>Confirm Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    required
                >


                <button
                    type="submit"
                    class="auth-button">

                    Create Account

                </button>

            </form>


            <p class="auth-footer">

                Already have an account?

                <a href="login.php">
                    Login
                </a>

            </p>

        </div>

    </div>

</body>

</html>
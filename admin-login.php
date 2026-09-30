<?php

session_start();

include "config/db.php";

$error = "";

if (isset($_POST["admin_login"])) {

    $email =
        trim($_POST["email"]);

    $password =
        $_POST["password"];


    if (
        empty($email) ||
        empty($password)
    ) {

        $error =
            "Please enter email and password.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                Admin_ID,
                Name,
                Email,
                Password
             FROM admin
             WHERE Email = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result =
            mysqli_stmt_get_result($stmt);


        if (mysqli_num_rows($result) == 1) {

            $admin =
                mysqli_fetch_assoc($result);


            if (
                $password ==
                $admin["Password"]
            ) {

                $_SESSION["admin_id"] =
                    $admin["Admin_ID"];

                $_SESSION["admin_name"] =
                    $admin["Name"];

                $_SESSION["admin_email"] =
                    $admin["Email"];


                header(
                    "Location: admin-dashboard.php"
                );

                exit();

            } else {

                $error =
                    "Incorrect password.";

            }

        } else {

            $error =
                "Admin account not found.";

        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Login - ParkEase</title>

    <link rel="stylesheet"
          href="css/style.css">

</head>

<body>

<div class="auth-container">


    <div class="auth-card">


        <div class="logo auth-logo">

            Park<span>Ease</span>

        </div>


        <h1>
            Admin Login
        </h1>


        <p class="auth-subtitle">
            Login to manage ParkEase
        </p>


        <?php if ($error != ""): ?>

            <div class="error-message">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="admin-login.php"
        >


            <div class="form-group">

                <label>
                    Admin Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter admin email"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >

            </div>


            <button
                type="submit"
                name="admin_login"
                class="auth-button"
            >

                Login as Admin

            </button>


        </form>


        <div class="auth-footer">

            <a href="login.php">
                ← User Login
            </a>

        </div>


    </div>

</div>

</body>

</html>
<?php

session_start();

include "config/db.php";

// Check admin login
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin-login.php");
    exit();
}

$admin_name = $_SESSION["admin_name"];


// ==========================================
// SEARCH USERS
// ==========================================

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}


// ==========================================
// GET USERS
// ==========================================

if ($search != "") {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            User_ID,
            Name,
            Email,
            Phone
         FROM users
         WHERE Name LIKE ?
            OR Email LIKE ?
            OR Phone LIKE ?
         ORDER BY User_ID DESC"
    );

    $search_value = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $search_value,
        $search_value,
        $search_value
    );

    mysqli_stmt_execute($stmt);

    $users =
        mysqli_stmt_get_result($stmt);

} else {

    $users = mysqli_query(
        $conn,
        "SELECT
            User_ID,
            Name,
            Email,
            Phone
         FROM users
         ORDER BY User_ID DESC"
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Manage Users - ParkEase
    </title>

    <link rel="stylesheet"
          href="css/style.css">

</head>

<body>


<!-- Navigation -->

<nav class="dashboard-nav">

    <div class="logo">

        Park<span>Ease</span>

    </div>


    <div class="dashboard-links">

        <a href="admin-dashboard.php">
            Dashboard
        </a>

        <a href="admin-users.php">
            Users
        </a>

        <a href="admin-bookings.php">
            Bookings
        </a>

        <a href="admin-slots.php">
            Parking Slots
        </a>

        <a href="admin-logout.php"
           class="logout-btn">

            Logout

        </a>

    </div>

</nav>


<!-- Main Content -->

<div class="dashboard-container">


    <div class="welcome-section">

        <h1>
            Manage Users
        </h1>

        <p>
            View registered ParkEase users.
        </p>

    </div>


    <!-- Search -->

    <div class="admin-section">

        <form
            method="GET"
            action="admin-users.php"
            class="admin-search-form"
        >

            <input
                type="text"
                name="search"
                value="<?php
                echo htmlspecialchars($search);
                ?>"
                placeholder="Search by name, email or phone"
            >

            <button
                type="submit"
                class="primary-btn"
            >
                Search
            </button>


            <?php if ($search != ""): ?>

                <a
                    href="admin-users.php"
                    class="secondary-btn"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- Users Table -->

    <div class="admin-section">

        <div class="section-header">

            <h2>
                Registered Users
            </h2>

            <span>
                <?php
                echo mysqli_num_rows($users);
                ?>
                users found
            </span>

        </div>


        <div class="admin-table-container">

            <table class="admin-table">

                <thead>

                    <tr>

                        <th>
                            User ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Phone
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        mysqli_num_rows($users) > 0
                    ): ?>


                        <?php while (
                            $user =
                            mysqli_fetch_assoc($users)
                        ): ?>


                            <tr>

                                <td>

                                    #<?php
                                    echo $user["User_ID"];
                                    ?>

                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $user["Name"]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["Email"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["Phone"]
                                    );
                                    ?>

                                </td>

                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="4"
                                class="no-data"
                            >

                                No users found.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


</div>


</body>

</html>
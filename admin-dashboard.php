<?php

session_start();

include "config/db.php";

// Check admin login
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin-login.php");
    exit();
}

$admin_name = $_SESSION["admin_name"];


// ==================================================
// DASHBOARD STATISTICS
// ==================================================

// Total users
$user_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users"
);

$total_users =
    mysqli_fetch_assoc($user_result)["total"];


// Total vehicles
$vehicle_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM vehicles"
);

$total_vehicles =
    mysqli_fetch_assoc($vehicle_result)["total"];


// Total parking slots
$slot_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM parking_slots"
);

$total_slots =
    mysqli_fetch_assoc($slot_result)["total"];


// Active bookings
$booking_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE Booking_Status = 'Booked'"
);

$active_bookings =
    mysqli_fetch_assoc($booking_result)["total"];


// Total revenue
$revenue_result = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(Amount), 0) AS total
     FROM payments
     WHERE Payment_Status = 'Paid'"
);

$total_revenue =
    mysqli_fetch_assoc($revenue_result)["total"];


// ==================================================
// PARKING SLOT COUNTS
// ==================================================

$available_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM parking_slots
     WHERE Status = 'Available'"
);

$available_slots =
    mysqli_fetch_assoc($available_result)["total"];


$occupied_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM parking_slots
     WHERE Status = 'Occupied'"
);

$occupied_slots =
    mysqli_fetch_assoc($occupied_result)["total"];


$reserved_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM parking_slots
     WHERE Status = 'Reserved'"
);

$reserved_slots =
    mysqli_fetch_assoc($reserved_result)["total"];


// ==================================================
// RECENT BOOKINGS
// ==================================================

$recent_bookings = mysqli_query(
    $conn,

    "SELECT
        b.Booking_ID,
        b.Booking_Date,
        b.Booking_Time,
        b.Duration,
        b.Booking_Status,

        u.Name AS User_Name,

        v.Vehicle_Number,

        p.Slot_Number

     FROM bookings b

     INNER JOIN users u
        ON b.User_ID = u.User_ID

     INNER JOIN vehicles v
        ON b.Vehicle_ID = v.Vehicle_ID

     INNER JOIN parking_slots p
        ON b.Slot_ID = p.Slot_ID

     ORDER BY b.Booking_ID DESC

     LIMIT 10"
);


// ==================================================
// PARKING SLOTS
// ==================================================

$parking_slots = mysqli_query(
    $conn,

    "SELECT
        Slot_ID,
        Slot_Number,
        Floor,
        Slot_Type,
        Status,
        Hourly_Rate

     FROM parking_slots

     ORDER BY Floor, Slot_Number"
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Admin Dashboard - ParkEase
    </title>

    <link rel="stylesheet"
          href="css/style.css">

</head>


<body>


<!-- ==================================================
     NAVIGATION
================================================== -->

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


<!-- ==================================================
     MAIN CONTENT
================================================== -->

<div class="dashboard-container">


    <!-- Welcome -->

    <div class="welcome-section">

        <h1>

            Welcome,
            <?php
            echo htmlspecialchars($admin_name);
            ?>

        </h1>

        <p>

            Manage your ParkEase parking system.

        </p>

    </div>


    <!-- ==================================================
         STATISTICS
    ================================================== -->

    <div class="admin-stats">


        <!-- Users -->

        <div class="admin-stat-card">

            <div class="admin-stat-icon">
                👥
            </div>

            <div>

                <p>
                    Total Users
                </p>

                <h2>
                    <?php
                    echo $total_users;
                    ?>
                </h2>

            </div>

        </div>


        <!-- Vehicles -->

        <div class="admin-stat-card">

            <div class="admin-stat-icon">
                🚗
            </div>

            <div>

                <p>
                    Total Vehicles
                </p>

                <h2>
                    <?php
                    echo $total_vehicles;
                    ?>
                </h2>

            </div>

        </div>


        <!-- Slots -->

        <div class="admin-stat-card">

            <div class="admin-stat-icon">
                🅿️
            </div>

            <div>

                <p>
                    Total Slots
                </p>

                <h2>
                    <?php
                    echo $total_slots;
                    ?>
                </h2>

            </div>

        </div>


        <!-- Active Bookings -->

        <div class="admin-stat-card">

            <div class="admin-stat-icon">
                📅
            </div>

            <div>

                <p>
                    Active Bookings
                </p>

                <h2>
                    <?php
                    echo $active_bookings;
                    ?>
                </h2>

            </div>

        </div>


        <!-- Revenue -->

        <div class="admin-stat-card">

            <div class="admin-stat-icon">
                💰
            </div>

            <div>

                <p>
                    Total Revenue
                </p>

                <h2>

                    ₹<?php
                    echo number_format(
                        $total_revenue,
                        2
                    );
                    ?>

                </h2>

            </div>

        </div>


    </div>


    <!-- ==================================================
         SLOT STATUS
    ================================================== -->

    <div class="admin-section">


        <div class="section-header">

            <h2>
                Parking Slot Status
            </h2>

        </div>


        <div class="slot-status-grid">


            <div class="slot-status available">

                <span>
                    Available
                </span>

                <strong>
                    <?php
                    echo $available_slots;
                    ?>
                </strong>

            </div>


            <div class="slot-status reserved">

                <span>
                    Reserved
                </span>

                <strong>
                    <?php
                    echo $reserved_slots;
                    ?>
                </strong>

            </div>


            <div class="slot-status occupied">

                <span>
                    Occupied
                </span>

                <strong>
                    <?php
                    echo $occupied_slots;
                    ?>
                </strong>

            </div>


        </div>


    </div>


    <!-- ==================================================
         RECENT BOOKINGS
    ================================================== -->

    <div class="admin-section">


        <div class="section-header">

            <h2>
                Recent Bookings
            </h2>

            <a href="admin-bookings.php">
                View All
            </a>

        </div>


        <div class="admin-table-container">


            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            Booking ID
                        </th>

                        <th>
                            User
                        </th>

                        <th>
                            Vehicle
                        </th>

                        <th>
                            Slot
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Duration
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php

                    if (
                        mysqli_num_rows(
                            $recent_bookings
                        ) > 0
                    ):

                    ?>


                        <?php while (
                            $booking =
                            mysqli_fetch_assoc(
                                $recent_bookings
                            )
                        ): ?>


                            <tr>

                                <td>

                                    #<?php
                                    echo $booking[
                                        "Booking_ID"
                                    ];
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $booking[
                                            "User_Name"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $booking[
                                            "Vehicle_Number"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $booking[
                                            "Slot_Number"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $booking[
                                                "Booking_Date"
                                            ]
                                        )
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $booking[
                                        "Duration"
                                    ];
                                    ?>

                                    hour(s)

                                </td>


                                <td>

                                    <span
                                        class="admin-status
                                        <?php
                                        echo strtolower(
                                            $booking[
                                                "Booking_Status"
                                            ]
                                        );
                                        ?>"
                                    >

                                        <?php
                                        echo $booking[
                                            "Booking_Status"
                                        ];
                                        ?>

                                    </span>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="no-data"
                            >

                                No bookings found.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


    <!-- ==================================================
         PARKING SLOTS
    ================================================== -->

    <div class="admin-section">


        <div class="section-header">

            <h2>
                Parking Slots
            </h2>

            <a href="admin-slots.php">
                Manage Slots
            </a>

        </div>


        <div class="admin-table-container">


            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            Slot
                        </th>

                        <th>
                            Floor
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Rate
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php while (
                        $slot =
                        mysqli_fetch_assoc(
                            $parking_slots
                        )
                    ): ?>


                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $slot[
                                            "Slot_Number"
                                        ]
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo $slot["Floor"];
                                ?>

                            </td>


                            <td>

                                <?php
                                echo $slot[
                                    "Slot_Type"
                                ];
                                ?>

                            </td>


                            <td>

                                ₹<?php
                                echo number_format(
                                    $slot[
                                        "Hourly_Rate"
                                    ],
                                    2
                                );
                                ?>

                            </td>


                            <td>

                                <span
                                    class="slot-status-badge
                                    <?php
                                    echo strtolower(
                                        $slot[
                                            "Status"
                                        ]
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo $slot[
                                        "Status"
                                    ];
                                    ?>

                                </span>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>


</body>

</html>
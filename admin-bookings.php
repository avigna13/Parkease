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
// FILTER
// ==========================================

$status_filter = "";

if (isset($_GET["status"])) {
    $status_filter = $_GET["status"];
}


// ==========================================
// GET BOOKINGS
// ==========================================

if (
    $status_filter == "Booked" ||
    $status_filter == "Cancelled" ||
    $status_filter == "Completed"
) {

    $stmt = mysqli_prepare(
        $conn,

        "SELECT
            b.Booking_ID,
            b.Booking_Date,
            b.Booking_Time,
            b.Duration,
            b.Booking_Status,

            u.Name AS User_Name,

            v.Vehicle_Number,
            v.Vehicle_Type,

            p.Slot_Number,
            p.Floor,

            pay.Amount,
            pay.Payment_Method,
            pay.Payment_Status

         FROM bookings b

         INNER JOIN users u
            ON b.User_ID = u.User_ID

         INNER JOIN vehicles v
            ON b.Vehicle_ID = v.Vehicle_ID

         INNER JOIN parking_slots p
            ON b.Slot_ID = p.Slot_ID

         LEFT JOIN parking_records pr
            ON b.Booking_ID = pr.Booking_ID

         LEFT JOIN payments pay
            ON pr.Record_ID = pay.Record_ID

         WHERE b.Booking_Status = ?

         ORDER BY b.Booking_ID DESC"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $status_filter
    );

    mysqli_stmt_execute($stmt);

    $bookings =
        mysqli_stmt_get_result($stmt);

} else {

    $bookings = mysqli_query(
        $conn,

        "SELECT
            b.Booking_ID,
            b.Booking_Date,
            b.Booking_Time,
            b.Duration,
            b.Booking_Status,

            u.Name AS User_Name,

            v.Vehicle_Number,
            v.Vehicle_Type,

            p.Slot_Number,
            p.Floor,

            pay.Amount,
            pay.Payment_Method,
            pay.Payment_Status

         FROM bookings b

         INNER JOIN users u
            ON b.User_ID = u.User_ID

         INNER JOIN vehicles v
            ON b.Vehicle_ID = v.Vehicle_ID

         LEFT JOIN parking_slots p
            ON b.Slot_ID = p.Slot_ID

         LEFT JOIN parking_records pr
            ON b.Booking_ID = pr.Booking_ID

         LEFT JOIN payments pay
            ON pr.Record_ID = pay.Record_ID

         ORDER BY b.Booking_ID DESC"
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
        Manage Bookings - ParkEase
    </title>

    <link rel="stylesheet"
          href="css/style.css">

</head>


<body>


<!-- ==========================================
     NAVIGATION
========================================== -->

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


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<div class="dashboard-container">


    <div class="welcome-section">

        <h1>
            Manage Bookings
        </h1>

        <p>
            View and monitor all parking bookings.
        </p>

    </div>


    <!-- ==========================================
         FILTER
    ========================================== -->

    <div class="admin-section">


        <div class="section-header">

            <h2>
                Booking Filter
            </h2>

        </div>


        <div class="booking-filter">


            <a
                href="admin-bookings.php"
                class="
                <?php
                echo $status_filter == ""
                    ? "active-filter"
                    : "";
                ?>"
            >
                All
            </a>


            <a
                href="admin-bookings.php?status=Booked"
                class="
                <?php
                echo $status_filter == "Booked"
                    ? "active-filter"
                    : "";
                ?>"
            >
                Booked
            </a>


            <a
                href="admin-bookings.php?status=Cancelled"
                class="
                <?php
                echo $status_filter == "Cancelled"
                    ? "active-filter"
                    : "";
                ?>"
            >
                Cancelled
            </a>


            <a
                href="admin-bookings.php?status=Completed"
                class="
                <?php
                echo $status_filter == "Completed"
                    ? "active-filter"
                    : "";
                ?>"
            >
                Completed
            </a>


        </div>


    </div>


    <!-- ==========================================
         BOOKINGS TABLE
    ========================================== -->

    <div class="admin-section">


        <div class="section-header">

            <h2>
                Booking Records
            </h2>

            <span>

                <?php
                echo mysqli_num_rows($bookings);
                ?>

                bookings

            </span>

        </div>


        <div class="admin-table-container">


            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            Booking
                        </th>

                        <th>
                            User
                        </th>

                        <th>
                            Vehicle
                        </th>

                        <th>
                            Parking Slot
                        </th>

                        <th>
                            Date & Time
                        </th>

                        <th>
                            Duration
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Payment
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        mysqli_num_rows($bookings) > 0
                    ): ?>


                        <?php while (
                            $booking =
                            mysqli_fetch_assoc(
                                $bookings
                            )
                        ): ?>


                            <tr>


                                <!-- Booking ID -->

                                <td>

                                    <strong>

                                        #<?php
                                        echo $booking[
                                            "Booking_ID"
                                        ];
                                        ?>

                                    </strong>

                                </td>


                                <!-- User -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $booking[
                                            "User_Name"
                                        ]
                                    );
                                    ?>

                                </td>


                                <!-- Vehicle -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $booking[
                                            "Vehicle_Number"
                                        ]
                                    );
                                    ?>

                                    <br>

                                    <small>

                                        <?php
                                        echo htmlspecialchars(
                                            $booking[
                                                "Vehicle_Type"
                                            ]
                                        );
                                        ?>

                                    </small>

                                </td>


                                <!-- Slot -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $booking[
                                                "Slot_Number"
                                            ]
                                        );
                                        ?>

                                    </strong>

                                    <br>

                                    <small>

                                        Floor
                                        <?php
                                        echo $booking[
                                            "Floor"
                                        ];
                                        ?>

                                    </small>

                                </td>


                                <!-- Date & Time -->

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

                                    <br>

                                    <small>

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime(
                                                $booking[
                                                    "Booking_Time"
                                                ]
                                            )
                                        );
                                        ?>

                                    </small>

                                </td>


                                <!-- Duration -->

                                <td>

                                    <?php
                                    echo $booking[
                                        "Duration"
                                    ];
                                    ?>

                                    hour(s)

                                </td>


                                <!-- Amount -->

                                <td>

                                    <?php

                                    if (
                                        $booking[
                                            "Amount"
                                        ] !== null
                                    ) {

                                        echo "₹"
                                            . number_format(
                                                $booking[
                                                    "Amount"
                                                ],
                                                2
                                            );

                                    } else {

                                        echo "—";

                                    }

                                    ?>

                                </td>


                                <!-- Payment -->

                                <td>

                                    <?php

                                    if (
                                        $booking[
                                            "Payment_Status"
                                        ] !== null
                                    ):

                                    ?>

                                        <span
                                            class="
                                            payment-badge
                                            <?php
                                            echo strtolower(
                                                $booking[
                                                    "Payment_Status"
                                                ]
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo $booking[
                                                "Payment_Status"
                                            ];
                                            ?>

                                        </span>


                                        <?php if (
                                            $booking[
                                                "Payment_Method"
                                            ]
                                            != null
                                        ): ?>

                                            <br>

                                            <small>

                                                <?php
                                                echo $booking[
                                                    "Payment_Method"
                                                ];
                                                ?>

                                            </small>

                                        <?php endif; ?>


                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <!-- Booking Status -->

                                <td>

                                    <span
                                        class="
                                        admin-status
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
                                colspan="9"
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


</div>


</body>

</html>
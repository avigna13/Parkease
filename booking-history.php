<?php

session_start();

include "config/db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// ==========================================
// CANCEL BOOKING
// ==========================================

if (isset($_POST["cancel_booking"])) {

    $booking_id = intval($_POST["booking_id"]);

    mysqli_begin_transaction($conn);

    try {

        // Check booking belongs to logged-in user
        // and get its slot

        $check_stmt = mysqli_prepare(
            $conn,
            "SELECT Booking_ID, Slot_ID, Booking_Status
             FROM bookings
             WHERE Booking_ID = ?
             AND User_ID = ?"
        );

        mysqli_stmt_bind_param(
            $check_stmt,
            "ii",
            $booking_id,
            $user_id
        );

        mysqli_stmt_execute($check_stmt);

        $check_result =
            mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) != 1) {

            throw new Exception(
                "Booking not found."
            );
        }

        $booking_data =
            mysqli_fetch_assoc($check_result);

        mysqli_stmt_close($check_stmt);


        // Check current booking status

        if ($booking_data["Booking_Status"] != "Booked") {

            throw new Exception(
                "This booking cannot be cancelled."
            );
        }


        // ==========================================
        // CANCEL BOOKING
        // ==========================================

        $cancel_stmt = mysqli_prepare(
            $conn,
            "UPDATE bookings
             SET Booking_Status = 'Cancelled'
             WHERE Booking_ID = ?
             AND User_ID = ?"
        );

        mysqli_stmt_bind_param(
            $cancel_stmt,
            "ii",
            $booking_id,
            $user_id
        );

        if (!mysqli_stmt_execute($cancel_stmt)) {

            throw new Exception(
                "Booking could not be cancelled."
            );
        }

        mysqli_stmt_close($cancel_stmt);


        // ==========================================
        // RELEASE PARKING SLOT
        // ==========================================

        $slot_stmt = mysqli_prepare(
            $conn,
            "UPDATE parking_slots
             SET Status = 'Available'
             WHERE Slot_ID = ?"
        );

        mysqli_stmt_bind_param(
            $slot_stmt,
            "i",
            $booking_data["Slot_ID"]
        );

        if (!mysqli_stmt_execute($slot_stmt)) {

            throw new Exception(
                "Parking slot could not be released."
            );
        }

        mysqli_stmt_close($slot_stmt);


        // Everything successful

        mysqli_commit($conn);


        header(
            "Location: booking-history.php"
        );

        exit();


    } catch (Exception $e) {

        mysqli_rollback($conn);

        $error = $e->getMessage();
    }
}
// Get user's bookings
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        b.Booking_ID,
        b.Booking_Date,
        b.Booking_Time,
        b.Duration,
        b.Booking_Status,

        v.Vehicle_Number,
        v.Vehicle_Type,
        v.Brand,

        p.Slot_Number,
        p.Floor,
        p.Hourly_Rate

     FROM bookings b

     INNER JOIN vehicles v
        ON b.Vehicle_ID = v.Vehicle_ID

     INNER JOIN parking_slots p
        ON b.Slot_ID = p.Slot_ID

     WHERE b.User_ID = ?

     ORDER BY b.Booking_ID DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$bookings = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Booking History - ParkEase</title>

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

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="vehicles.php">
            My Vehicles
        </a>

        <a href="booking.php">
            Book Parking
        </a>

        <a href="booking-history.php">
            My Bookings
        </a>

        <a href="logout.php"
           class="logout-btn">
            Logout
        </a>

    </div>

</nav>


<!-- Main Content -->

<div class="dashboard-container">


    <div class="welcome-section">

        <h1>
            My Bookings
        </h1>

        <p>
            View your parking booking history.
        </p>

    </div>


    <?php if (mysqli_num_rows($bookings) == 0): ?>


        <div class="no-vehicles">

            <div class="quick-icon">
                📅
            </div>

            <h3>
                No bookings yet
            </h3>

            <p>
                You have not made any parking bookings.
            </p>

            <br>

            <a href="booking.php"
               class="primary-btn">

                Book Parking

            </a>

        </div>


    <?php else: ?>


        <div class="booking-grid">


            <?php while (
                $booking =
                mysqli_fetch_assoc($bookings)
            ): ?>


                <div class="booking-card">


                    <div class="booking-header">

                        <div>

                            <h3>

                                Booking #
                                <?php
                                echo $booking["Booking_ID"];
                                ?>

                            </h3>

                            <span>

                                Slot
                                <?php
                                echo htmlspecialchars(
                                    $booking["Slot_Number"]
                                );
                                ?>

                            </span>

                        </div>


                        <span class="booking-status">

                            <?php
                            echo $booking["Booking_Status"];
                            ?>

                        </span>

                    </div>


                    <div class="booking-details">


                        <div class="booking-detail">

                            <strong>
                                🚗 Vehicle
                            </strong>

                            <p>

                                <?php
                                echo htmlspecialchars(
                                    $booking["Vehicle_Number"]
                                );
                                ?>

                                -
                                <?php
                                echo htmlspecialchars(
                                    $booking["Vehicle_Type"]
                                );
                                ?>

                            </p>

                        </div>


                        <div class="booking-detail">

                            <strong>
                                🅿️ Parking Slot
                            </strong>

                            <p>

                                <?php
                                echo htmlspecialchars(
                                    $booking["Slot_Number"]
                                );
                                ?>

                                - Floor
                                <?php
                                echo $booking["Floor"];
                                ?>

                            </p>

                        </div>


                        <div class="booking-detail">

                            <strong>
                                📅 Date
                            </strong>

                            <p>

                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $booking["Booking_Date"]
                                    )
                                );
                                ?>

                            </p>

                        </div>


                        <div class="booking-detail">

                            <strong>
                                🕐 Time
                            </strong>

                            <p>

                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $booking["Booking_Time"]
                                    )
                                );
                                ?>

                            </p>

                        </div>


                        <div class="booking-detail">

                            <strong>
                                ⏱️ Duration
                            </strong>

                            <p>

                                <?php
                                echo $booking["Duration"];
                                ?>

                                Hour(s)

                            </p>

                        </div>


                        <div class="booking-detail">

                            <strong>
                                💰 Rate
                            </strong>

                            <p>

                                ₹<?php
                                echo number_format(
                                    $booking["Hourly_Rate"],
                                    2
                                );
                                ?>

                                / hour

                            </p>

                        </div>


                    </div>


                    <div class="booking-total">

                        Estimated Amount:

                        <strong>

                            ₹<?php

                            $total =
                                $booking["Hourly_Rate"]
                                *
                                $booking["Duration"];

                            echo number_format(
                                $total,
                                2
                            );

                            ?>

                        </strong>

                    </div>
<?php if ($booking["Booking_Status"] == "Booked"): ?>

    <form
        method="POST"
        action="booking-history.php"
        onsubmit="return confirm(
            'Are you sure you want to cancel this booking?'
        );"
    >

        <input
            type="hidden"
            name="booking_id"
            value="<?php
            echo $booking["Booking_ID"];
            ?>"
        >

        <button
            type="submit"
            name="cancel_booking"
            class="cancel-booking-btn"
        >

            Cancel Booking

        </button>

    </form>

<?php endif; ?>

                </div>


            <?php endwhile; ?>


        </div>


    <?php endif; ?>


</div>


</body>

</html>
<?php

session_start();

include "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

if (!isset($_GET["booking_id"])) {
    header("Location: booking-history.php");
    exit();
}

$booking_id = intval($_GET["booking_id"]);


$stmt = mysqli_prepare(
    $conn,

    "SELECT
        b.Booking_ID,
        b.Booking_Date,
        b.Booking_Time,
        b.Duration,

        v.Vehicle_Number,

        p.Slot_Number,

        pr.Record_ID,

        pay.Amount,
        pay.Payment_Method,
        pay.Payment_Status,
        pay.Transaction_ID,
        pay.Payment_Date

     FROM bookings b

     INNER JOIN vehicles v
        ON b.Vehicle_ID = v.Vehicle_ID

     INNER JOIN parking_slots p
        ON b.Slot_ID = p.Slot_ID

     INNER JOIN parking_records pr
        ON b.Booking_ID = pr.Booking_ID

     INNER JOIN payments pay
        ON pr.Record_ID = pay.Record_ID

     WHERE b.Booking_ID = ?
     AND b.User_ID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $booking_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: booking-history.php");
    exit();
}

$data = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Payment Successful - ParkEase</title>

    <link rel="stylesheet"
          href="css/style.css">

</head>

<body>

<nav class="dashboard-nav">

    <div class="logo">
        Park<span>Ease</span>
    </div>

</nav>


<div class="dashboard-container">


    <div class="payment-success-card">


        <div class="success-icon">
            ✓
        </div>


        <h1>
            Payment Successful!
        </h1>


        <p>
            Your parking booking has been confirmed.
        </p>


        <div class="receipt">


            <p>
                <strong>Booking ID:</strong>
                #<?php echo $data["Booking_ID"]; ?>
            </p>


            <p>
                <strong>Vehicle:</strong>
                <?php
                echo htmlspecialchars(
                    $data["Vehicle_Number"]
                );
                ?>
            </p>


            <p>
                <strong>Parking Slot:</strong>
                <?php
                echo htmlspecialchars(
                    $data["Slot_Number"]
                );
                ?>
            </p>


            <p>
                <strong>Date:</strong>
                <?php
                echo date(
                    "d M Y",
                    strtotime(
                        $data["Booking_Date"]
                    )
                );
                ?>
            </p>


            <p>
                <strong>Time:</strong>
                <?php
                echo date(
                    "h:i A",
                    strtotime(
                        $data["Booking_Time"]
                    )
                );
                ?>
            </p>


            <p>
                <strong>Duration:</strong>
                <?php
                echo $data["Duration"];
                ?>
                hour(s)
            </p>


            <p>
                <strong>Payment Method:</strong>
                <?php
                echo htmlspecialchars(
                    $data["Payment_Method"]
                );
                ?>
            </p>


            <p>
                <strong>Transaction ID:</strong>
                <?php
                echo htmlspecialchars(
                    $data["Transaction_ID"]
                );
                ?>
            </p>


            <div class="receipt-total">

                Amount Paid:

                ₹<?php
                echo number_format(
                    $data["Amount"],
                    2
                );
                ?>

            </div>


        </div>


        <div class="success-actions">

            <a
                href="booking-history.php"
                class="primary-btn"
            >
                My Bookings
            </a>


            <a
                href="dashboard.php"
                class="edit-btn"
            >
                Dashboard
            </a>

        </div>


    </div>


</div>

</body>

</html>
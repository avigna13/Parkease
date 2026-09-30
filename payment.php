<?php

session_start();

include "config/db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$error = "";


// ==================================================
// CHECK BOOKING ID
// ==================================================

if (!isset($_GET["booking_id"])) {
    header("Location: booking-history.php");
    exit();
}

$booking_id = intval($_GET["booking_id"]);


// ==================================================
// GET BOOKING DETAILS
// ==================================================

$stmt = mysqli_prepare(
    $conn,

    "SELECT
        b.Booking_ID,
        b.User_ID,
        b.Vehicle_ID,
        b.Slot_ID,
        b.Booking_Date,
        b.Booking_Time,
        b.Duration,
        b.Booking_Status,

        v.Vehicle_Number,
        v.Vehicle_Type,

        p.Slot_Number,
        p.Floor,
        p.Hourly_Rate

     FROM bookings b

     INNER JOIN vehicles v
        ON b.Vehicle_ID = v.Vehicle_ID

     INNER JOIN parking_slots p
        ON b.Slot_ID = p.Slot_ID

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


// Booking not found

if (mysqli_num_rows($result) != 1) {

    header("Location: booking-history.php");
    exit();

}

$booking = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// ==================================================
// CALCULATE AMOUNT
// ==================================================

$total_amount =
    $booking["Hourly_Rate"]
    *
    $booking["Duration"];


// ==================================================
// CHECK EXISTING PARKING RECORD
// ==================================================

$record_check = mysqli_prepare(
    $conn,

    "SELECT Record_ID
     FROM parking_records
     WHERE Booking_ID = ?"
);

mysqli_stmt_bind_param(
    $record_check,
    "i",
    $booking_id
);

mysqli_stmt_execute($record_check);

$record_result =
    mysqli_stmt_get_result($record_check);

$existing_record = mysqli_fetch_assoc(
    $record_result
);

mysqli_stmt_close($record_check);


// ==================================================
// CHECK EXISTING PAYMENT
// ==================================================

$existing_payment = null;

if ($existing_record) {

    $payment_check = mysqli_prepare(
        $conn,

        "SELECT
            Payment_ID,
            Amount,
            Payment_Method,
            Payment_Status,
            Transaction_ID
         FROM payments
         WHERE Record_ID = ?"
    );

    mysqli_stmt_bind_param(
        $payment_check,
        "i",
        $existing_record["Record_ID"]
    );

    mysqli_stmt_execute($payment_check);

    $payment_result =
        mysqli_stmt_get_result(
            $payment_check
        );

    $existing_payment =
        mysqli_fetch_assoc(
            $payment_result
        );

    mysqli_stmt_close($payment_check);
}


// ==================================================
// PROCESS PAYMENT
// ==================================================

if (isset($_POST["make_payment"])) {

    $payment_method =
        $_POST["payment_method"];


    // Validate payment method

    $allowed_methods = [
        "Cash",
        "UPI",
        "Card",
        "Net Banking"
    ];

    if (!in_array(
        $payment_method,
        $allowed_methods
    )) {

        $error =
            "Invalid payment method.";

    } elseif (
        $existing_payment &&
        $existing_payment["Payment_Status"] == "Paid"
    ) {

        $error =
            "Payment has already been completed.";

    } else {

        // Start transaction

        mysqli_begin_transaction($conn);


        try {

            // ==========================================
            // CREATE PARKING RECORD
            // ==========================================

            if (!$existing_record) {

                $entry_datetime =
                    $booking["Booking_Date"]
                    . " "
                    . $booking["Booking_Time"];


                $exit_datetime =
                    date(
                        "Y-m-d H:i:s",
                        strtotime(
                            $entry_datetime
                        )
                        +
                        ($booking["Duration"] * 3600)
                    );


                $record_stmt = mysqli_prepare(
                    $conn,

                    "INSERT INTO parking_records
                    (
                        Booking_ID,
                        Entry_Time,
                        Exit_Time,
                        Total_Hours
                    )
                    VALUES (?, ?, ?, ?)"
                );


                $total_hours =
                    $booking["Duration"];


                mysqli_stmt_bind_param(
                    $record_stmt,
                    "issd",
                    $booking_id,
                    $entry_datetime,
                    $exit_datetime,
                    $total_hours
                );


                if (!mysqli_stmt_execute(
                    $record_stmt
                )) {

                    throw new Exception(
                        "Parking record could not be created."
                    );

                }


                $record_id =
                    mysqli_insert_id($conn);


                mysqli_stmt_close(
                    $record_stmt
                );


            } else {

                $record_id =
                    $existing_record["Record_ID"];

            }


            // ==========================================
            // CREATE PAYMENT
            // ==========================================

            $transaction_id =
                "TXN"
                . date("YmdHis")
                . rand(100, 999);


            $payment_stmt = mysqli_prepare(
                $conn,

                "INSERT INTO payments
                (
                    Record_ID,
                    Amount,
                    Payment_Method,
                    Payment_Status,
                    Transaction_ID
                )
                VALUES (?, ?, ?, 'Paid', ?)"
            );


            mysqli_stmt_bind_param(
                $payment_stmt,
                "idss",
                $record_id,
                $total_amount,
                $payment_method,
                $transaction_id
            );


            if (!mysqli_stmt_execute(
                $payment_stmt
            )) {

                throw new Exception(
                    "Payment could not be recorded."
                );

            }


            mysqli_stmt_close(
                $payment_stmt
            );


            // ==========================================
            // COMMIT
            // ==========================================

            mysqli_commit($conn);


            // Redirect to success page

            header(
                "Location: payment-success.php?booking_id="
                . $booking_id
            );

            exit();


        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error =
                $e->getMessage();

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Payment - ParkEase</title>

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


<!-- Main -->

<div class="dashboard-container">


    <div class="welcome-section">

        <h1>
            Parking Payment
        </h1>

        <p>
            Complete your parking payment.
        </p>

    </div>


    <?php if ($error != ""): ?>

        <div class="error-message">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <div class="payment-card">


        <h2>
            Booking Summary
        </h2>


        <div class="payment-details">


            <p>
                <strong>
                    Booking ID:
                </strong>

                #<?php
                echo $booking["Booking_ID"];
                ?>

            </p>


            <p>
                <strong>
                    Vehicle:
                </strong>

                <?php
                echo htmlspecialchars(
                    $booking["Vehicle_Number"]
                );
                ?>

            </p>


            <p>
                <strong>
                    Vehicle Type:
                </strong>

                <?php
                echo htmlspecialchars(
                    $booking["Vehicle_Type"]
                );
                ?>

            </p>


            <p>
                <strong>
                    Parking Slot:
                </strong>

                <?php
                echo htmlspecialchars(
                    $booking["Slot_Number"]
                );
                ?>

            </p>


            <p>
                <strong>
                    Floor:
                </strong>

                <?php
                echo $booking["Floor"];
                ?>

            </p>


            <p>
                <strong>
                    Date:
                </strong>

                <?php
                echo date(
                    "d M Y",
                    strtotime(
                        $booking["Booking_Date"]
                    )
                );
                ?>

            </p>


            <p>
                <strong>
                    Start Time:
                </strong>

                <?php
                echo date(
                    "h:i A",
                    strtotime(
                        $booking["Booking_Time"]
                    )
                );
                ?>

            </p>


            <p>
                <strong>
                    Duration:
                </strong>

                <?php
                echo $booking["Duration"];
                ?>
                hour(s)

            </p>


            <p>
                <strong>
                    Hourly Rate:
                </strong>

                ₹<?php
                echo number_format(
                    $booking["Hourly_Rate"],
                    2
                );
                ?>

            </p>


        </div>


        <div class="payment-total">

            Total Amount

            <strong>

                ₹<?php
                echo number_format(
                    $total_amount,
                    2
                );
                ?>

            </strong>

        </div>


        <form
            method="POST"
            action="payment.php?booking_id=<?php echo $booking_id; ?>"
        >


            <div class="form-group">

                <label>
                    Payment Method
                </label>

                <select
                    name="payment_method"
                    required
                >

                    <option value="">
                        Select Payment Method
                    </option>

                    <option value="UPI">
                        UPI
                    </option>

                    <option value="Card">
                        Card
                    </option>

                    <option value="Net Banking">
                        Net Banking
                    </option>

                    <option value="Cash">
                        Cash
                    </option>

                </select>

            </div>


            <button
                type="submit"
                name="make_payment"
                class="auth-button"
            >

                Pay ₹<?php
                echo number_format(
                    $total_amount,
                    2
                );
                ?>

            </button>


        </form>


    </div>


</div>


</body>

</html>
<?php

session_start();

include "config/db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";

$selected_date = "";
$selected_time = "";
$selected_duration = 0;

$available_slots_result = false;


// ==================================================
// GET USER VEHICLES
// ==================================================

$vehicle_stmt = mysqli_prepare(
    $conn,
    "SELECT Vehicle_ID,
            Vehicle_Number,
            Vehicle_Type,
            Brand
     FROM vehicles
     WHERE User_ID = ?
     ORDER BY Vehicle_ID DESC"
);

mysqli_stmt_bind_param(
    $vehicle_stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($vehicle_stmt);

$vehicles = mysqli_stmt_get_result($vehicle_stmt);


// ==================================================
// GET PARKING SLOTS
// ==================================================

// Initially, do not load slots.
// Slots will be loaded after the user selects
// date, time and duration.

$slot_result = false;

// ==================================================
// CHECK AVAILABLE SLOTS
// ==================================================

if (isset($_POST["check_slots"])) {

    $selected_date =
        $_POST["booking_date"] ?? "";

    $selected_time =
        $_POST["start_time"] ?? "";

    $selected_duration =
        intval($_POST["duration"] ?? 0);


    if (
        empty($selected_date) ||
        empty($selected_time) ||
        $selected_duration <= 0
    ) {

        $error =
            "Please select date, start time and duration.";

    } else {

        /*
         * Find slots that:
         *
         * 1. Are not physically Occupied
         * 2. Do not have an overlapping booking
         */

        $available_slots_stmt = mysqli_prepare(
            $conn,
            "SELECT
                ps.Slot_ID,
                ps.Slot_Number,
                ps.Floor,
                ps.Slot_Type,
                ps.Hourly_Rate
             FROM parking_slots ps
             WHERE ps.Status != 'Occupied'
             AND NOT EXISTS (
                 SELECT 1
                 FROM bookings b
                 WHERE b.Slot_ID = ps.Slot_ID
                 AND b.Booking_Date = ?
                 AND b.Booking_Status = 'Booked'
                 AND b.Booking_Time <
                     ADDTIME(
                         ?,
                         SEC_TO_TIME(? * 3600)
                     )
                 AND ADDTIME(
                         b.Booking_Time,
                         SEC_TO_TIME(
                             b.Duration * 3600
                         )
                     ) > ?
             )
             ORDER BY ps.Floor, ps.Slot_Number"
        );


        mysqli_stmt_bind_param(
            $available_slots_stmt,
            "ssis",
            $selected_date,
            $selected_time,
            $selected_duration,
            $selected_time
        );


        mysqli_stmt_execute(
            $available_slots_stmt
        );


        $available_slots_result =
            mysqli_stmt_get_result(
                $available_slots_stmt
            );
    }
}
// ==================================================
// CREATE BOOKING
// ==================================================

if (isset($_POST["book_parking"])) {

    $vehicle_id = intval($_POST["vehicle_id"]);
    $slot_id = intval($_POST["slot_id"]);

    $booking_date = $_POST["booking_date"] ?? "";
    $start_time = $_POST["start_time"] ?? "";
    $duration = intval($_POST["duration"]);


    // ------------------------------------------
    // Basic validation
    // ------------------------------------------

    if (
        $vehicle_id <= 0 ||
        $slot_id <= 0 ||
        empty($booking_date) ||
        empty($start_time) ||
        $duration <= 0
    ) {

        $error = "Please fill all booking details.";

    } else {

        // ------------------------------------------
        // Check vehicle belongs to logged-in user
        // ------------------------------------------

        $vehicle_check = mysqli_prepare(
            $conn,
            "SELECT Vehicle_ID, Vehicle_Type
             FROM vehicles
             WHERE Vehicle_ID = ?
             AND User_ID = ?"
        );

        mysqli_stmt_bind_param(
            $vehicle_check,
            "ii",
            $vehicle_id,
            $user_id
        );

        mysqli_stmt_execute($vehicle_check);

        $vehicle_result =
            mysqli_stmt_get_result($vehicle_check);


        if (mysqli_num_rows($vehicle_result) != 1) {

            $error = "Invalid vehicle selected.";

        } else {

            $vehicle = mysqli_fetch_assoc(
                $vehicle_result
            );

            $vehicle_type = $vehicle["Vehicle_Type"];


            // ------------------------------------------
            // Check parking slot
            // ------------------------------------------

            $slot_check = mysqli_prepare(
                $conn,
                "SELECT Slot_ID,
                        Slot_Number,
                        Slot_Type,
                        Hourly_Rate,
                        Status
                 FROM parking_slots
                 WHERE Slot_ID = ?"
            );

            mysqli_stmt_bind_param(
                $slot_check,
                "i",
                $slot_id
            );

            mysqli_stmt_execute($slot_check);

            $slot_result_check =
                mysqli_stmt_get_result($slot_check);


            if (mysqli_num_rows($slot_result_check) != 1) {

                $error = "Invalid parking slot.";

            } else {

                $slot = mysqli_fetch_assoc(
                    $slot_result_check
                );


                // ------------------------------------------
                // Only physically occupied slots are blocked
                // ------------------------------------------

                if ($slot["Status"] == "Occupied") {

                    $error =
                        "This parking slot is currently occupied.";

                }

                // ------------------------------------------
                // Check vehicle type
                // ------------------------------------------

                elseif ($slot["Slot_Type"] != $vehicle_type) {

                    $error =
                        "This parking slot is not suitable for your vehicle type.";

                }

                else {

                    // --------------------------------------
                    // Calculate total amount
                    // --------------------------------------

                    $total_amount =
                        $slot["Hourly_Rate"] * $duration;


                    // --------------------------------------
                    // CHECK OVERLAPPING BOOKINGS
                    // --------------------------------------

                    /*
                     * Existing booking:
                     *
                     * 10:30 - 11:30
                     *
                     * New booking:
                     *
                     * 10:30 - 11:30
                     *
                     * Result: REJECT
                     *
                     *
                     * Existing booking:
                     *
                     * 10:30 - 11:30
                     *
                     * New booking:
                     *
                     * 11:30 - 12:30
                     *
                     * Result: ALLOW
                     */

                    $overlap_stmt = mysqli_prepare(
                        $conn,
                        "SELECT Booking_ID
                         FROM bookings
                         WHERE Slot_ID = ?
                         AND Booking_Date = ?
                         AND Booking_Status = 'Booked'
                         AND Booking_Time <
                             ADDTIME(
                                 ?,
                                 SEC_TO_TIME(? * 3600)
                             )
                         AND ADDTIME(
                                 Booking_Time,
                                 SEC_TO_TIME(Duration * 3600)
                             ) > ?"
                    );


                    mysqli_stmt_bind_param(
                        $overlap_stmt,
                        "issis",
                        $slot_id,
                        $booking_date,
                        $start_time,
                        $duration,
                        $start_time
                    );


                    mysqli_stmt_execute(
                        $overlap_stmt
                    );


                    $overlap_result =
                        mysqli_stmt_get_result(
                            $overlap_stmt
                        );


                    // --------------------------------------
                    // Booking conflict found
                    // --------------------------------------

                    if (
                        mysqli_num_rows(
                            $overlap_result
                        ) > 0
                    ) {

                        $error =
                            "This parking slot is already booked for the selected time.";

                        mysqli_stmt_close(
                            $overlap_stmt
                        );

                    }

                    // --------------------------------------
                    // No conflict → create booking
                    // --------------------------------------

                    else {

                        mysqli_stmt_close(
                            $overlap_stmt
                        );


                        mysqli_begin_transaction(
                            $conn
                        );


                        try {

                            // ----------------------------------
                            // Insert booking
                            // ----------------------------------

                            $booking_stmt = mysqli_prepare(
                                $conn,
                                "INSERT INTO bookings
                                (
                                    User_ID,
                                    Vehicle_ID,
                                    Slot_ID,
                                    Booking_Date,
                                    Booking_Time,
                                    Duration,
                                    Booking_Status
                                )
                                VALUES
                                (?, ?, ?, ?, ?, ?, 'Booked')"
                            );


                            mysqli_stmt_bind_param(
                                $booking_stmt,
                                "iiissi",
                                $user_id,
                                $vehicle_id,
                                $slot_id,
                                $booking_date,
                                $start_time,
                                $duration
                            );


                            if (
                                !mysqli_stmt_execute(
                                    $booking_stmt
                                )
                            ) {

                                throw new Exception(
                                    "Booking could not be created."
                                );
                            }


                            $booking_id =
                                mysqli_insert_id($conn);


                            mysqli_stmt_close(
                                $booking_stmt
                            );


                            // ----------------------------------
                            // DO NOT change parking slot status
                            //
                            // The slot remains available for
                            // other non-overlapping time periods.
                            // ----------------------------------


                            // ----------------------------------
                            // Complete transaction
                            // ----------------------------------

                            mysqli_commit($conn);


                            // ----------------------------------
                            // Go to payment
                            // ----------------------------------

                            header(
                                "Location: payment.php?booking_id="
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
            }


            mysqli_stmt_close(
                $slot_check
            );
        }


        mysqli_stmt_close(
            $vehicle_check
        );
    }
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Book Parking - ParkEase</title>

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
            Book Parking
        </h1>

        <p>
            Select your vehicle and reserve an available
            parking slot.
        </p>

    </div>


    <!-- Messages -->

    <?php if ($message != ""): ?>

        <div class="success-message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error != ""): ?>

        <div class="error-message">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <?php if (mysqli_num_rows($vehicles) == 0): ?>


        <div class="vehicle-form-card">

            <h2>
                No Vehicle Registered
            </h2>

            <p>
                You need to register a vehicle before
                booking a parking slot.
            </p>

            <br>

            <a href="vehicles.php"
               class="primary-btn">

                Add Vehicle

            </a>

        </div>


    <?php else: ?>


        <!-- Booking Form -->

        <div class="vehicle-form-card">

            <h2>
                Parking Details
            </h2>


            <form method="POST"
                  action="booking.php">


                <!-- Vehicle -->

                <div class="form-group">

                    <label>
                        Select Vehicle
                    </label>

                    <select
                        name="vehicle_id"
                        required
                    >

                        <option value="">
                            Select your vehicle
                        </option>


                        <?php while (
                            $vehicle =
                            mysqli_fetch_assoc($vehicles)
                        ): ?>

                            <option
                                value="<?php
                                echo $vehicle["Vehicle_ID"];
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $vehicle["Vehicle_Number"]
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    $vehicle["Vehicle_Type"]
                                );
                                ?>

                                <?php
                                if (!empty(
                                    $vehicle["Brand"]
                                )) {

                                    echo " - "
                                        . htmlspecialchars(
                                            $vehicle["Brand"]
                                        );
                                }
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>



                <!-- Date -->

                <div class="form-row">


                    <div class="form-group">

                        <label>
                            Booking Date
                        </label>

                        <input
                            type="date"
                            name="booking_date"
                            min="<?php echo date('Y-m-d');?>"
                            value="<?php echo htmlspecialchars($selected_date); ?>"
                            required
                        >

                    </div>


                    <!-- Time -->

                    <div class="form-group">

                        <label>
                            Start Time
                        </label>

                        <input
                            type="time"
                            name="start_time"
                             value="<?php echo htmlspecialchars($selected_time); ?>"
                            required
                        >

                    </div>


                </div>


                <!-- Duration -->

                <div class="form-group">

                    <label>
                        Parking Duration
                    </label>

                    <select
                        name="duration"
                        required
                    >

                        <option value="">
                            Select duration
                        </option>

                       <option
    value="1"
    <?php
    if ($selected_duration == 1) {
        echo "selected";
    }
    ?>
>
    1 Hour
</option>

                        <option
    value="2"
    <?php
    if ($selected_duration == 2) {
        echo "selected";
    }
    ?>
>
    2 Hour
</option>

                        <option
    value="3"
    <?php
    if ($selected_duration == 3) {
        echo "selected";
    }
    ?>
>
    3 Hour
</option>
<option
    value="4"
    <?php
    if ($selected_duration == 4) {
        echo "selected";
    }
    ?>
>
    4 Hour
</option>
                        <option
    value="5"
    <?php
    if ($selected_duration == 5) {
        echo "selected";
    }
    ?>
>
    5 Hour
</option>

                        <option
    value="6"
    <?php
    if ($selected_duration == 6) {
        echo "selected";
    }
    ?>
>
    6 Hour
</option>
                       <option
    value="8"
    <?php
    if ($selected_duration == 8) {
        echo "selected";
    }
    ?>
>
    8 Hour
</option>

                        <option
    value="12"
    <?php
    if ($selected_duration == 12) {
        echo "selected";
    }
    ?>
>
    12 Hour
</option>

                    </select>

                </div>

<button
    type="submit"
    name="check_slots"
    class="auth-button"
>
    Check Available Slots
</button>
<?php if ($available_slots_result !== false): ?>

    <div class="form-group">

        <label>
            Select Available Parking Slot
        </label>

        <select
            name="slot_id"
            required
        >

            <option value="">
                Select available slot
            </option>

            <?php while (
                $slot =
                mysqli_fetch_assoc(
                    $available_slots_result
                )
            ): ?>

                <option
                    value="<?php
                    echo $slot["Slot_ID"];
                    ?>"
                >

                    Slot
                    <?php
                    echo htmlspecialchars(
                        $slot["Slot_Number"]
                    );
                    ?>

                    -
                    Floor
                    <?php
                    echo $slot["Floor"];
                    ?>

                    -
                    <?php
                    echo htmlspecialchars(
                        $slot["Slot_Type"]
                    );
                    ?>

                    -
                    ₹<?php
                    echo number_format(
                        $slot["Hourly_Rate"],
                        2
                    );
                    ?>/hour

                </option>

            <?php endwhile; ?>

        </select>

    </div>


    <button
        type="submit"
        name="book_parking"
        class="auth-button"
    >
        Continue Booking
    </button>

<?php endif; ?>
                


            </form>

        </div>


        <!-- Available Slots -->

        <div class="vehicle-list">

            <h2>
                Available Parking Slots
            </h2>


            <div class="slot-grid">


                <?php

                // Get slots again because the
                // previous result was used by the form

                $available_slots_result =
                    mysqli_query(
                        $conn,
                        "SELECT Slot_ID,
                                Slot_Number,
                                Floor,
                                Slot_Type,
                                Hourly_Rate
                         FROM parking_slots
                         WHERE Status = 'Available'
                         ORDER BY Floor, Slot_Number"
                    );

                ?>


                <?php while (
                    $slot =
                    mysqli_fetch_assoc(
                        $available_slots_result
                    )
                ): ?>


                    <div class="slot-card">


                        <div class="slot-icon">
                            🅿️
                        </div>


                        <h3>

                            Slot
                            <?php
                            echo htmlspecialchars(
                                $slot["Slot_Number"]
                            );
                            ?>

                        </h3>


                        <p>

                            Floor
                            <?php
                            echo $slot["Floor"];
                            ?>

                        </p>


                        <p>

                            Type:
                            <strong>
                                <?php
                                echo $slot["Slot_Type"];
                                ?>
                            </strong>

                        </p>


                        <p class="slot-price">

                            ₹<?php
                            echo number_format(
                                $slot["Hourly_Rate"],
                                2
                            );
                            ?>

                            / hour

                        </p>


                        <span class="available-badge">

                            Available

                        </span>


                    </div>


                <?php endwhile; ?>


            </div>

        </div>


    <?php endif; ?>


</div>


</body>

</html>
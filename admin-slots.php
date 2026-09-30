<?php

session_start();

include "config/db.php";

// Check admin login
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin-login.php");
    exit();
}

$message = "";
$error = "";


// ==================================================
// ADD PARKING SLOT
// ==================================================

if (isset($_POST["add_slot"])) {

    $slot_number = trim($_POST["slot_number"]);
    $floor = intval($_POST["floor"]);
    $slot_type = $_POST["slot_type"];
    $hourly_rate = floatval($_POST["hourly_rate"]);

    if (
        empty($slot_number) ||
        $floor <= 0 ||
        empty($slot_type) ||
        $hourly_rate <= 0
    ) {

        $error = "Please fill all slot details.";

    } else {

        // Check duplicate slot number

        $check = mysqli_prepare(
            $conn,
            "SELECT Slot_ID
             FROM parking_slots
             WHERE Slot_Number = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $slot_number
        );

        mysqli_stmt_execute($check);

        $check_result =
            mysqli_stmt_get_result($check);

        if (mysqli_num_rows($check_result) > 0) {

            $error =
                "This slot number already exists.";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO parking_slots
                (
                    Slot_Number,
                    Floor,
                    Slot_Type,
                    Status,
                    Hourly_Rate
                )
                VALUES (?, ?, ?, 'Available', ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sisd",
                $slot_number,
                $floor,
                $slot_type,
                $hourly_rate
            );

            if (mysqli_stmt_execute($stmt)) {

                $message =
                    "Parking slot added successfully.";

            } else {

                $error =
                    "Parking slot could not be added.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}


// ==================================================
// UPDATE PARKING SLOT
// ==================================================

if (isset($_POST["update_slot"])) {

    $slot_id = intval($_POST["slot_id"]);
    $slot_number = trim($_POST["slot_number"]);
    $floor = intval($_POST["floor"]);
    $slot_type = $_POST["slot_type"];
    $status = $_POST["status"];
    $hourly_rate = floatval($_POST["hourly_rate"]);


    if (
        $slot_id <= 0 ||
        empty($slot_number) ||
        $floor <= 0 ||
        empty($slot_type) ||
        empty($status) ||
        $hourly_rate <= 0
    ) {

        $error =
            "Please enter valid slot details.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE parking_slots
             SET
                Slot_Number = ?,
                Floor = ?,
                Slot_Type = ?,
                Status = ?,
                Hourly_Rate = ?
             WHERE Slot_ID = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sissdi",
            $slot_number,
            $floor,
            $slot_type,
            $status,
            $hourly_rate,
            $slot_id
        );

        if (mysqli_stmt_execute($stmt)) {

            $message =
                "Parking slot updated successfully.";

        } else {

            $error =
                "Parking slot could not be updated.";
        }

        mysqli_stmt_close($stmt);
    }
}


// ==================================================
// DELETE PARKING SLOT
// ==================================================

if (isset($_POST["delete_slot"])) {

    $slot_id =
        intval($_POST["slot_id"]);


    if ($slot_id <= 0) {

        $error =
            "Invalid parking slot.";

    } else {

        // Check whether slot has bookings

        $check = mysqli_prepare(
            $conn,

            "SELECT Booking_ID
             FROM bookings
             WHERE Slot_ID = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $check,
            "i",
            $slot_id
        );

        mysqli_stmt_execute($check);

        $check_result =
            mysqli_stmt_get_result($check);


        if (
            mysqli_num_rows(
                $check_result
            ) > 0
        ) {

            $error =
                "This slot cannot be deleted because it has booking records.";

        } else {

            $stmt = mysqli_prepare(
                $conn,

                "DELETE FROM parking_slots
                 WHERE Slot_ID = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $slot_id
            );


            if (mysqli_stmt_execute($stmt)) {

                $message =
                    "Parking slot deleted successfully.";

            } else {

                $error =
                    "Parking slot could not be deleted.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}


// ==================================================
// GET ALL SLOTS
// ==================================================

$slots = mysqli_query(
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Parking Slots - ParkEase
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

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

        <a
            href="admin-logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>

</nav>


<!-- ==================================================
     MAIN CONTENT
================================================== -->

<div class="dashboard-container">


    <div class="welcome-section">

        <h1>
            Manage Parking Slots
        </h1>

        <p>
            Add, update and manage parking slots.
        </p>

    </div>


    <!-- ==================================================
         MESSAGES
    ================================================== -->

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


    <!-- ==================================================
         ADD SLOT
    ================================================== -->

    <div class="admin-section">

        <div class="section-header">

            <h2>
                Add New Parking Slot
            </h2>

        </div>


        <form
            method="POST"
            action="admin-slots.php"
            class="slot-admin-form"
        >


            <div class="form-group">

                <label>
                    Slot Number
                </label>

                <input
                    type="text"
                    name="slot_number"
                    placeholder="Example: D401"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Floor
                </label>

                <input
                    type="number"
                    name="floor"
                    min="1"
                    placeholder="Example: 4"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Slot Type
                </label>

                <select
                    name="slot_type"
                    required
                >

                    <option value="">
                        Select type
                    </option>

                    <option value="Bike">
                        Bike
                    </option>

                    <option value="Car">
                        Car
                    </option>

                    <option value="SUV">
                        SUV
                    </option>

                    <option value="Truck">
                        Truck
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Hourly Rate
                </label>

                <input
                    type="number"
                    name="hourly_rate"
                    min="1"
                    step="0.01"
                    placeholder="Example: 40"
                    required
                >

            </div>


            <div class="slot-form-button">

                <button
                    type="submit"
                    name="add_slot"
                    class="primary-btn"
                >

                    + Add Slot

                </button>

            </div>


        </form>

    </div>


    <!-- ==================================================
         ALL SLOTS
    ================================================== -->

    <div class="admin-section">


        <div class="section-header">

            <h2>
                All Parking Slots
            </h2>

            <span>

                <?php
                echo mysqli_num_rows($slots);
                ?>

                slots

            </span>

        </div>


        <div class="admin-table-container">


            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

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
                            Hourly Rate
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php while (
                        $slot =
                        mysqli_fetch_assoc($slots)
                    ): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                #<?php
                                echo $slot["Slot_ID"];
                                ?>

                            </td>


                            <!-- Slot -->

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $slot["Slot_Number"]
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- Floor -->

                            <td>

                                <?php
                                echo $slot["Floor"];
                                ?>

                            </td>


                            <!-- Type -->

                            <td>

                                <?php
                                echo $slot["Slot_Type"];
                                ?>

                            </td>


                            <!-- Rate -->

                            <td>

                                ₹<?php
                                echo number_format(
                                    $slot["Hourly_Rate"],
                                    2
                                );
                                ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <span
                                    class="
                                    slot-status-badge
                                    <?php
                                    echo strtolower(
                                        $slot["Status"]
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo $slot["Status"];
                                    ?>

                                </span>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="slot-actions">


                                    <!-- Edit -->

                                    <button
                                        type="button"
                                        class="edit-slot-btn"
                                        onclick="showEditForm(
                                            <?php
                                            echo $slot[
                                                "Slot_ID"
                                            ];
                                            ?>
                                        )"
                                    >

                                        Edit

                                    </button>


                                    <!-- Delete -->

                                    <form
                                        method="POST"
                                        action="admin-slots.php"
                                        onsubmit="return confirm(
                                            'Are you sure you want to delete this slot?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="slot_id"
                                            value="<?php
                                            echo $slot[
                                                "Slot_ID"
                                            ];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_slot"
                                            class="delete-slot-btn"
                                        >

                                            Delete

                                        </button>

                                    </form>


                                </div>


                                <!-- Edit Form -->

                                <div
                                    id="edit-<?php
                                    echo $slot[
                                        "Slot_ID"
                                    ];
                                    ?>"
                                    class="edit-slot-form"
                                >


                                    <form
                                        method="POST"
                                        action="admin-slots.php"
                                    >


                                        <input
                                            type="hidden"
                                            name="slot_id"
                                            value="<?php
                                            echo $slot[
                                                "Slot_ID"
                                            ];
                                            ?>"
                                        >


                                        <input
                                            type="text"
                                            name="slot_number"
                                            value="<?php
                                            echo htmlspecialchars(
                                                $slot[
                                                    "Slot_Number"
                                                ]
                                            );
                                            ?>"
                                            required
                                        >


                                        <input
                                            type="number"
                                            name="floor"
                                            value="<?php
                                            echo $slot[
                                                "Floor"
                                            ];
                                            ?>"
                                            min="1"
                                            required
                                        >


                                        <select
                                            name="slot_type"
                                            required
                                        >

                                            <option
                                                value="Bike"
                                                <?php
                                                echo $slot[
                                                    "Slot_Type"
                                                ] == "Bike"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Bike
                                            </option>

                                            <option
                                                value="Car"
                                                <?php
                                                echo $slot[
                                                    "Slot_Type"
                                                ] == "Car"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Car
                                            </option>

                                            <option
                                                value="SUV"
                                                <?php
                                                echo $slot[
                                                    "Slot_Type"
                                                ] == "SUV"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                SUV
                                            </option>

                                            <option
                                                value="Truck"
                                                <?php
                                                echo $slot[
                                                    "Slot_Type"
                                                ] == "Truck"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Truck
                                            </option>

                                        </select>


                                        <select
                                            name="status"
                                            required
                                        >

                                            <option
                                                value="Available"
                                                <?php
                                                echo $slot[
                                                    "Status"
                                                ] == "Available"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Available
                                            </option>

                                            <option
                                                value="Occupied"
                                                <?php
                                                echo $slot[
                                                    "Status"
                                                ] == "Occupied"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Occupied
                                            </option>

                                            <option
                                                value="Reserved"
                                                <?php
                                                echo $slot[
                                                    "Status"
                                                ] == "Reserved"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Reserved
                                            </option>

                                        </select>


                                        <input
                                            type="number"
                                            name="hourly_rate"
                                            value="<?php
                                            echo $slot[
                                                "Hourly_Rate"
                                            ];
                                            ?>"
                                            min="1"
                                            step="0.01"
                                            required
                                        >


                                        <button
                                            type="submit"
                                            name="update_slot"
                                            class="save-slot-btn"
                                        >

                                            Save Changes

                                        </button>


                                    </form>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>


<script>

function showEditForm(slotId) {

    const form =
        document.getElementById(
            "edit-" + slotId
        );

    if (form.style.display === "block") {

        form.style.display = "none";

    } else {

        form.style.display = "block";

    }

}

</script>


</body>

</html>
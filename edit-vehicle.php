<?php

session_start();

include "config/db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Check vehicle ID
if (!isset($_GET["id"])) {
    header("Location: vehicles.php");
    exit();
}

$vehicle_id = intval($_GET["id"]);


// Get vehicle details
$stmt = mysqli_prepare(
    $conn,
    "SELECT Vehicle_ID,
            Vehicle_Number,
            Vehicle_Type,
            Brand,
            Color
     FROM vehicles
     WHERE Vehicle_ID = ?
     AND User_ID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $vehicle_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: vehicles.php");
    exit();
}

$vehicle = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


$message = "";
$error = "";


// Update vehicle
if (isset($_POST["update_vehicle"])) {

    $vehicle_number = strtoupper(
        trim($_POST["vehicle_number"])
    );

    $vehicle_type = $_POST["vehicle_type"];

    $brand = trim($_POST["brand"]);

    $color = trim($_POST["color"]);


    // Check whether another vehicle
    // already uses this number

    $check = mysqli_prepare(
        $conn,
        "SELECT Vehicle_ID
         FROM vehicles
         WHERE Vehicle_Number = ?
         AND Vehicle_ID != ?"
    );

    mysqli_stmt_bind_param(
        $check,
        "si",
        $vehicle_number,
        $vehicle_id
    );

    mysqli_stmt_execute($check);

    $check_result = mysqli_stmt_get_result($check);


    if (mysqli_num_rows($check_result) > 0) {

        $error = "This vehicle number is already registered.";

    } else {

        $update = mysqli_prepare(
            $conn,
            "UPDATE vehicles
             SET Vehicle_Number = ?,
                 Vehicle_Type = ?,
                 Brand = ?,
                 Color = ?
             WHERE Vehicle_ID = ?
             AND User_ID = ?"
        );

        mysqli_stmt_bind_param(
            $update,
            "ssssii",
            $vehicle_number,
            $vehicle_type,
            $brand,
            $color,
            $vehicle_id,
            $user_id
        );


        if (mysqli_stmt_execute($update)) {

            header("Location: vehicles.php");
            exit();

        } else {

            $error = "Unable to update vehicle.";

        }

        mysqli_stmt_close($update);
    }

    mysqli_stmt_close($check);
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Vehicle - ParkEase</title>

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
            Edit Vehicle
        </h1>

        <p>
            Update your vehicle information.
        </p>

    </div>


    <div class="vehicle-form-card">


        <?php if ($error != ""): ?>

            <div class="error-message">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form method="POST"
              action="edit-vehicle.php?id=<?php echo $vehicle_id; ?>">


            <div class="form-row">


                <div class="form-group">

                    <label>
                        Vehicle Number
                    </label>

                    <input
                        type="text"
                        name="vehicle_number"
                        value="<?php echo htmlspecialchars($vehicle["Vehicle_Number"]); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Vehicle Type
                    </label>

                    <select
                        name="vehicle_type"
                        required
                    >

                        <option
                            value="Bike"
                            <?php
                            if ($vehicle["Vehicle_Type"] == "Bike")
                                echo "selected";
                            ?>
                        >
                            Bike
                        </option>

                        <option
                            value="Car"
                            <?php
                            if ($vehicle["Vehicle_Type"] == "Car")
                                echo "selected";
                            ?>
                        >
                            Car
                        </option>

                        <option
                            value="SUV"
                            <?php
                            if ($vehicle["Vehicle_Type"] == "SUV")
                                echo "selected";
                            ?>
                        >
                            SUV
                        </option>

                        <option
                            value="Truck"
                            <?php
                            if ($vehicle["Vehicle_Type"] == "Truck")
                                echo "selected";
                            ?>
                        >
                            Truck
                        </option>

                    </select>

                </div>

            </div>


            <div class="form-row">


                <div class="form-group">

                    <label>
                        Brand
                    </label>

                    <input
                        type="text"
                        name="brand"
                        value="<?php echo htmlspecialchars($vehicle["Brand"] ?? ""); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Color
                    </label>

                    <input
                        type="text"
                        name="color"
                        value="<?php echo htmlspecialchars($vehicle["Color"] ?? ""); ?>"
                    >

                </div>

            </div>


            <div class="vehicle-actions">

                <button
                    type="submit"
                    name="update_vehicle"
                    class="auth-button">

                    Update Vehicle

                </button>


                <a
                    href="vehicles.php"
                    class="delete-btn">

                    Cancel

                </a>

            </div>


        </form>

    </div>

</div>

</body>

</html>
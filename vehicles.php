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


// ==============================
// ADD VEHICLE
// ==============================

if (isset($_POST["add_vehicle"])) {

    $vehicle_number = strtoupper(trim($_POST["vehicle_number"]));
    $vehicle_type = $_POST["vehicle_type"];
    $brand = trim($_POST["brand"]);
    $color = trim($_POST["color"]);


    // Check if vehicle number already exists
    $check = mysqli_prepare(
        $conn,
        "SELECT Vehicle_ID
         FROM vehicles
         WHERE Vehicle_Number = ?"
    );

    mysqli_stmt_bind_param(
        $check,
        "s",
        $vehicle_number
    );

    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);


    if (mysqli_num_rows($result) > 0) {

        $error = "This vehicle number is already registered.";

    } else {

        // Insert vehicle
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO vehicles
            (User_ID, Vehicle_Number, Vehicle_Type, Brand, Color)
            VALUES (?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "issss",
            $user_id,
            $vehicle_number,
            $vehicle_type,
            $brand,
            $color
        );


        if (mysqli_stmt_execute($stmt)) {

            $message = "Vehicle added successfully.";

        } else {

            $error = "Unable to add vehicle.";

        }

        mysqli_stmt_close($stmt);
    }

    mysqli_stmt_close($check);
}


// ==============================
// DELETE VEHICLE
// ==============================

if (isset($_GET["delete"])) {

    $vehicle_id = intval($_GET["delete"]);


    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM vehicles
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

    mysqli_stmt_close($stmt);


    header("Location: vehicles.php");
    exit();
}


// ==============================
// GET USER VEHICLES
// ==============================

$stmt = mysqli_prepare(
    $conn,
    "SELECT Vehicle_ID,
            Vehicle_Number,
            Vehicle_Type,
            Brand,
            Color
     FROM vehicles
     WHERE User_ID = ?
     ORDER BY Vehicle_ID DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$vehicles = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Vehicles - ParkEase</title>

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
            My Vehicles
        </h1>

        <p>
            Add and manage your registered vehicles.
        </p>

    </div>


    <!-- Messages -->

    <?php if ($message != ""): ?>

        <div class="success-message">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <?php if ($error != ""): ?>

        <div class="error-message">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <!-- Add Vehicle -->

    <div class="vehicle-form-card">

        <h2>
            Add New Vehicle
        </h2>


        <form method="POST"
              action="vehicles.php">


            <div class="form-row">


                <div class="form-group">

                    <label>
                        Vehicle Number
                    </label>

                    <input
                        type="text"
                        name="vehicle_number"
                        placeholder="e.g. MH27AB1234"
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

                        <option value="">
                            Select Type
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


            </div>


            <div class="form-row">


                <div class="form-group">

                    <label>
                        Brand
                    </label>

                    <input
                        type="text"
                        name="brand"
                        placeholder="e.g. Honda"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Color
                    </label>

                    <input
                        type="text"
                        name="color"
                        placeholder="e.g. Black"
                    >

                </div>


            </div>


            <button
                type="submit"
                name="add_vehicle"
                class="auth-button">

                + Add Vehicle

            </button>


        </form>

    </div>


    <!-- Vehicle List -->

    <div class="vehicle-list">

        <h2>
            Your Vehicles
        </h2>


        <?php if (mysqli_num_rows($vehicles) == 0): ?>


            <div class="no-vehicles">

                <div class="quick-icon">
                    🚗
                </div>

                <h3>
                    No vehicles registered
                </h3>

                <p>
                    Add your first vehicle using
                    the form above.
                </p>

            </div>


        <?php else: ?>


            <div class="vehicle-grid">


                <?php while ($vehicle = mysqli_fetch_assoc($vehicles)): ?>


                    <div class="vehicle-card">


                        <div class="vehicle-card-top">

                            <div class="vehicle-icon">

                                <?php

                                if ($vehicle["Vehicle_Type"] == "Bike") {

                                    echo "🏍️";

                                } else {

                                    echo "🚗";

                                }

                                ?>

                            </div>


                            <div>

                                <h3>

                                    <?php

                                    echo htmlspecialchars(
                                        $vehicle["Vehicle_Number"]
                                    );

                                    ?>

                                </h3>

                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $vehicle["Vehicle_Type"]
                                    );

                                    ?>

                                </span>

                            </div>

                        </div>


                        <div class="vehicle-details">

                            <p>
                                <strong>Brand:</strong>

                                <?php

                                echo htmlspecialchars(
                                    $vehicle["Brand"] ?: "Not specified"
                                );

                                ?>

                            </p>


                            <p>
                                <strong>Color:</strong>

                                <?php

                                echo htmlspecialchars(
                                    $vehicle["Color"] ?: "Not specified"
                                );

                                ?>

                            </p>

                        </div>


                        <div class="vehicle-actions">

                            <a
                                href="edit-vehicle.php?id=<?php echo $vehicle["Vehicle_ID"]; ?>"
                                class="edit-btn">

                                Edit

                            </a>


                            <a
                                href="vehicles.php?delete=<?php echo $vehicle["Vehicle_ID"]; ?>"
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this vehicle?');">

                                Delete

                            </a>

                        </div>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
<?php

session_start();

include "config/db.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Get number of vehicles
$vehicle_query = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM vehicles
     WHERE User_ID = ?"
);

mysqli_stmt_bind_param(
    $vehicle_query,
    "i",
    $user_id
);

mysqli_stmt_execute($vehicle_query);

$vehicle_result = mysqli_stmt_get_result($vehicle_query);

$vehicle_data = mysqli_fetch_assoc($vehicle_result);

$total_vehicles = $vehicle_data["total"];


// Get number of bookings
$booking_query = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE User_ID = ?"
);

mysqli_stmt_bind_param(
    $booking_query,
    "i",
    $user_id
);

mysqli_stmt_execute($booking_query);

$booking_result = mysqli_stmt_get_result($booking_query);

$booking_data = mysqli_fetch_assoc($booking_result);

$total_bookings = $booking_data["total"];


// Get available parking slots
$slot_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM parking_slots
     WHERE Status = 'Available'"
);

$slot_data = mysqli_fetch_assoc($slot_query);

$available_slots = $slot_data["total"];

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard - ParkEase</title>

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


<!-- Dashboard -->

<div class="dashboard-container">

    <div class="welcome-section">

        <h1>
            Welcome, <?php echo htmlspecialchars($user_name); ?>!
        </h1>

        <p>
            Manage your parking easily with ParkEase.
        </p>

    </div>


    <!-- Statistics -->

    <div class="dashboard-cards">


        <div class="dashboard-card">

            <div class="dashboard-icon">
                🅿️
            </div>

            <div>

                <h3>
                    <?php echo $available_slots; ?>
                </h3>

                <p>
                    Available Slots
                </p>

            </div>

        </div>


        <div class="dashboard-card">

            <div class="dashboard-icon">
                🚗
            </div>

            <div>

                <h3>
                    <?php echo $total_vehicles; ?>
                </h3>

                <p>
                    My Vehicles
                </p>

            </div>

        </div>


        <div class="dashboard-card">

            <div class="dashboard-icon">
                📅
            </div>

            <div>

                <h3>
                    <?php echo $total_bookings; ?>
                </h3>

                <p>
                    Total Bookings
                </p>

            </div>

        </div>


    </div>


    <!-- Quick Actions -->

    <div class="quick-section">

        <h2>
            Quick Actions
        </h2>


        <div class="quick-container">


            <a href="booking.php"
               class="quick-card">

                <div class="quick-icon">
                    🅿️
                </div>

                <h3>
                    Find Parking
                </h3>

                <p>
                    Find and reserve an available
                    parking slot.
                </p>

            </a>


            <a href="vehicles.php"
               class="quick-card">

                <div class="quick-icon">
                    🚗
                </div>

                <h3>
                    Manage Vehicles
                </h3>

                <p>
                    Add and manage your vehicles.
                </p>

            </a>


            <a href="booking-history.php"
               class="quick-card">

                <div class="quick-icon">
                    📋
                </div>

                <h3>
                    Booking History
                </h3>

                <p>
                    View your previous parking bookings.
                </p>

            </a>


        </div>

    </div>


    <!-- Welcome Message -->

    <div class="dashboard-info">

        <h2>
            Smart Parking with ParkEase
        </h2>

        <p>
            Find available parking slots, register your
            vehicles and reserve parking spaces easily.
        </p>

        <a href="booking.php"
           class="primary-btn">

            Find Parking

        </a>

    </div>

</div>

</body>

</html>
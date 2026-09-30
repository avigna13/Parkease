<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "ParkEase";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

?>
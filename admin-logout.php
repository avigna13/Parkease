<?php

session_start();

// Remove admin session
unset($_SESSION["admin_id"]);
unset($_SESSION["admin_name"]);
unset($_SESSION["admin_email"]);

header("Location: admin-login.php");

exit();

?>
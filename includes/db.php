<?php
$host ="localhost";
$user ="root";
$password ="";
$database ="melody_masters_db";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database connection Failed: " . mysqli_connect_error());
}


?>
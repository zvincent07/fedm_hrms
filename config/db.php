<?php
$servername = "127.0.0.1:4306";
$username = "root";
$password = "";
$dbname = "fedm_hrms";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

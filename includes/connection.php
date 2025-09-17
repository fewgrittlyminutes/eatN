<?php
$servername = "localhost:3309";
$username = "root";
$password = "";
$dbname = "n_eeattt";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<?php
// Replace these with your InfinityFree database credentials
$host = "localhost"; // e.g., sqlxxx.infinityfree.com
$username = "root";  // e.g., if0_3xxxxxxx
$password = "";      // Your InfinityFree password
$dbname = "sushrusha"; // e.g., if0_3xxxxxxx_sushrusha

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}


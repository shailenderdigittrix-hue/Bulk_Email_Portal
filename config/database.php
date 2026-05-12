<?php

// for window ------------
// $host = "localhost";
// $port = "3306";
// $dbname = "bulk_email_portal";
// $username = "root";
// $password = "";

// For mac ---------------
$host = "localhost";
$port = "8889";
$dbname = "bulk_email_portal";
$username = "root";
$password = "root";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Database Connected Successfully";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
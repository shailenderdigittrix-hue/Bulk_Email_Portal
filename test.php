<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "bulk_email_portal",
    8889
);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

echo "Connected Successfully";
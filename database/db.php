<?php
$host = "localhost";
$dbname = "shoes_shop";
$username = "root";
$password = "";

$pdo = new PDO('mysql:host=localhost;dbname=shoes_shop', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
?>
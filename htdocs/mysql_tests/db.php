<?php
/**
 * MySQL connection helper (used by mysql_tests pages).
 * Works both on XAMPP (localhost) and Docker Compose (mysql service).
 */

$host = getenv('MYSQL_HOST') ?: 'localhost';
$port = getenv('MYSQL_PORT') ?: '3306';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: '';
$db   = getenv('MYSQL_DATABASE') ?: 'cs306';

$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>

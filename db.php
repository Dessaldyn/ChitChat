<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // sesuaikan dengan password MySQL Anda
$dbname = 'chitchat';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

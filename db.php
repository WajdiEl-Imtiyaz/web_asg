<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'wd1_asg';

$conn = new mysqli($host, $user, $pass, $dbname);

if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
}
?>
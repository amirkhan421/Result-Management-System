<?php

$host     = "localhost"; 
$db_user  = "root";      
$db_pass  = "";          
$db_name  = "result_management"; 


$conn = new mysqli($host, $db_user, $db_pass, $db_name);


if ($conn->connect_error) {
    
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}


$conn->set_charset("utf8mb4");
?>
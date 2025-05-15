<?php
// Database configuration
$config = [
    'db_host' => 'localhost',
    'db_name' => 'location_immobiliere',
    'db_user' => 'root',
    'db_pass' => '',
];

// Set timezone
date_default_timezone_set('Africa/Algiers');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
function getDbConnection() {
    global $config;
    $conn = new mysqli(
        $config['db_host'], 
        $config['db_user'], 
        $config['db_pass'], 
        $config['db_name']
    );
    
    if ($conn->connect_error) {
        die("Connexion échouée : " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

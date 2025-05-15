<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../property/property_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour ajouter des favoris.']);
    exit;
}

// Check if property_id is provided
if (!isset($_POST['property_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de propriété manquant.']);
    exit;
}

$property_id = intval($_POST['property_id']);
$user_id = $_SESSION['user_id'];

// Get database connection
$conn = getDbConnection();

// Check if property is already in favorites
if (isPropertyInFavorites($conn, $user_id, $property_id)) {
    // Remove from favorites
    $result = removeFromFavorites($conn, $user_id, $property_id);
} else {
    // Add to favorites
    $result = addToFavorites($conn, $user_id, $property_id);
}

$conn->close();

echo json_encode($result);

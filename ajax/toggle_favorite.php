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
$check_sql = "SELECT id FROM favoris WHERE user_id = ? AND annonce_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user_id, $property_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Already in favorites, remove it
    $delete_sql = "DELETE FROM favoris WHERE user_id = ? AND annonce_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $user_id, $property_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Retiré des favoris']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression des favoris: ' . $conn->error]);
    }
} else {
    // Not in favorites, add it
    $insert_sql = "INSERT INTO favoris (user_id, annonce_id, date_ajout) VALUES (?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $user_id, $property_id);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Ajouté aux favoris']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout aux favoris: ' . $conn->error]);
    }
}

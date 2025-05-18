<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../booking/booking_functions.php';

// Check if user is logged in
$logged_in = isLoggedIn();

// Return error if not logged in
if (!$logged_in) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action.'
    ]);
    exit;
}

// Get database connection
$conn = getDbConnection();

// Get POST data
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validate status
$valid_statuses = ['confirmed', 'cancelled', 'completed'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Statut de réservation invalide.'
    ]);
    exit;
}

// Update booking status
$result = updateBookingStatus($conn, $booking_id, $status, $_SESSION['user_id']);

// Return result
header('Content-Type: application/json');
echo json_encode($result);

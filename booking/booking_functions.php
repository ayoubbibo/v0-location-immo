<?php
// Booking helper functions

function createBooking($conn, $property_id, $user_id, $check_in, $check_out, $guests, $total_price, $status = 'pending') {
    // Check if property is available for these dates
    if (!isPropertyAvailable($conn, $property_id, $check_in, $check_out)) {
        return ['success' => false, 'message' => 'Cette propriété n\'est pas disponible pour les dates sélectionnées.'];
    }
    
    // Calculate number of nights
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $nights = $check_out_date->diff($check_in_date)->days;
    
    // Insert booking
    $sql = "INSERT INTO bookings (property_id, user_id, check_in, check_out, guests, nights, total_price, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissiiis", $property_id, $user_id, $check_in, $check_out, $guests, $nights, $total_price, $status);
    
    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        
        // Create notification for property owner
        $property_owner_id = getPropertyOwnerId($conn, $property_id);
        if ($property_owner_id) {
            createNotification($conn, $property_owner_id, 'booking', 'Nouvelle demande de réservation', 
                               'Vous avez reçu une nouvelle demande de réservation.', 
                               ['booking_id' => $booking_id]);
        }
        
        return ['success' => true, 'booking_id' => $booking_id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la création de la réservation: ' . $conn->error];
    }
}

function isPropertyAvailable($conn, $property_id, $check_in, $check_out) {
    // Check if property exists and is active
    $property_sql = "SELECT id, date_debut, date_fin FROM properties WHERE id = ? AND valide = 1";
    $property_stmt = $conn->prepare($property_sql);
    $property_stmt->bind_param("i", $property_id);
    $property_stmt->execute();
    $property_result = $property_stmt->get_result();
    
    if ($property_result->num_rows === 0) {
        return false;
    }
    
    $property = $property_result->fetch_assoc();
    
    // Check if requested dates are within property's available dates
    if ($check_in < $property['date_debut'] || $check_out > $property['date_fin']) {
        return false;
    }
    
    // Check if there are any overlapping confirmed bookings
    $booking_sql = "SELECT id FROM bookings 
                   WHERE property_id = ? 
                   AND status IN ('confirmed', 'paid') 
                   AND ((check_in <= ? AND check_out >= ?) 
                   OR (check_in <= ? AND check_out >= ?) 
                   OR (check_in >= ? AND check_out <= ?))";
    
    $booking_stmt = $conn->prepare($booking_sql);
    $booking_stmt->bind_param("issssss", $property_id, $check_out, $check_in, $check_in, $check_in, $check_in, $check_out);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    
    return $booking_result->num_rows === 0;
}

// function getPropertyOwnerId($conn, $property_id) {
//     $sql = "SELECT user_id FROM properties WHERE id = ?";
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("i", $property_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
    
//     if ($result->num_rows === 1) {
//         $row = $result->fetch_assoc();
//         return $row['user_id'];
//     }
    
//     return null;
// }

function getUserBookings($conn, $user_id, $status = null) {
    $sql = "SELECT b.*, a.title, a.address, a.photos, a.price 
            FROM bookings b 
            JOIN properties a ON b.property_id = a.id 
            WHERE b.user_id = ?";
    
    if ($status) {
        $sql .= " AND b.status = ?";
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($status) {
        $stmt->bind_param("is", $user_id, $status);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        // Process photos
        $photos = explode(',', $row['photos']);
        $row['main_photo'] = !empty($photos[0]) ? '../properties/' . $photos[0] : '../images/default.jpg';
        
        $bookings[] = $row;
    }
    
    return $bookings;
}

function getHostBookings($conn, $host_id, $status = null) {
    $sql = "SELECT b.*, a.title, a.address, a.photos, a.price, u.username, u.email 
            FROM bookings b 
            JOIN properties a ON b.property_id = a.id 
            JOIN users u ON b.user_id = u.id 
            WHERE a.user_id = ?";
    
    if ($status) {
        $sql .= " AND b.status = ?";
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($status) {
        $stmt->bind_param("is", $host_id, $status);
    } else {
        $stmt->bind_param("i", $host_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        // Process photos
        $photos = explode(',', $row['photos']);
        $row['main_photo'] = !empty($photos[0]) ? '../properties/' . $photos[0] : '../images/default.jpg';
        
        $bookings[] = $row;
    }
    
    return $bookings;
}

function updateBookingStatus($conn, $booking_id, $status, $user_id = null) {
    // Check if user has permission to update this booking
    if ($user_id) {
        $check_sql = "SELECT b.id 
                     FROM bookings b 
                     JOIN properties a ON b.property_id = a.id 
                     WHERE b.id = ? AND (b.user_id = ? OR a.user_id = ?)";
        
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iii", $booking_id, $user_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            return ['success' => false, 'message' => 'Vous n\'avez pas la permission de modifier cette réservation.'];
        }
    }
    
    // Update booking status
    $sql = "UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        // Get booking details for notification
        $booking_sql = "SELECT b.*, a.user_id as host_id, a.title 
                       FROM bookings b 
                       JOIN properties a ON b.property_id = a.id 
                       WHERE b.id = ?";
        $booking_stmt = $conn->prepare($booking_sql);
        $booking_stmt->bind_param("i", $booking_id);
        $booking_stmt->execute();
        $booking_result = $booking_stmt->get_result();
        
        if ($booking_result->num_rows === 1) {
            $booking = $booking_result->fetch_assoc();
            
            // Create notification based on status
            if ($status === 'confirmed') {
                // Notify guest that booking is confirmed
                createNotification($conn, $booking['user_id'], 'booking', 'Réservation confirmée', 
                                  'Votre réservation pour ' . $booking['title'] . ' a été confirmée.', 
                                  ['booking_id' => $booking_id]);
            } elseif ($status === 'cancelled') {
                // Notify appropriate party about cancellation
                if ($user_id === $booking['user_id']) {
                    // Guest cancelled, notify host
                    createNotification($conn, $booking['host_id'], 'booking', 'Réservation annulée', 
                                      'Une réservation pour ' . $booking['title'] . ' a été annulée par le client.', 
                                      ['booking_id' => $booking_id]);
                } else {
                    // Host cancelled, notify guest
                    createNotification($conn, $booking['user_id'], 'booking', 'Réservation annulée', 
                                      'Votre réservation pour ' . $booking['title'] . ' a été annulée par l\'hôte.', 
                                      ['booking_id' => $booking_id]);
                }
            }
        }
        
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour de la réservation: ' . $conn->error];
    }
}

function getBookingDetails($conn, $booking_id, $user_id = null) {
    $sql = "SELECT b.*, a.title, a.address, a.photos, a.price, a.area, a.housing_type, a.number_of_rooms, b.host_id as host_id, 
                  h.username as host_name, h.email as host_email, h.phone as host_phone,
                  g.username as guest_name, g.email as guest_email, g.phone as guest_phone
           FROM bookings b 
           JOIN properties a ON b.property_id = a.id 
           JOIN users g ON b.user_id = g.id
           JOIN users h ON b.host_id = h.id
           WHERE b.id = ?";

    if ($user_id) {
        $sql .= " AND (b.user_id = ? OR a.user_id = ?)";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($user_id) {
        $stmt->bind_param("iii", $booking_id, $user_id, $user_id);
    } else {
        $stmt->bind_param("i", $booking_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $booking = $result->fetch_assoc();
        
        // Process photos
        $photos = explode(',', $booking['photos']);
        $booking['main_photo'] = !empty($photos[0]) ? '../properties/' . $photos[0] : '../images/default.jpg';
        $booking['all_photos'] = array_map(function($photo) {
            return '../properties/' . $photo;
        }, $photos);
        
        return $booking;
    }
    
    return null;
}

function createNotification($conn, $user_id, $type, $title, $message, $data = []) {
    $data_json = json_encode($data);
    
    $sql = "INSERT INTO notifications (user_id, type, title, message, data, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    // Correct the bind_param call
    $stmt->bind_param("issss", $user_id, $type, $title, $message, $data_json);
    
    return $stmt->execute();
}
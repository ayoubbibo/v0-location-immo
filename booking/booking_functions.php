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
    $property_sql = "SELECT id, start_date, end_date FROM properties WHERE id = ? AND validated = 1";
    $property_stmt = $conn->prepare($property_sql);
    $property_stmt->bind_param("i", $property_id);
    $property_stmt->execute();
    $property_result = $property_stmt->get_result();
    
    if ($property_result->num_rows === 0) {
        return false;
    }
    
    $property = $property_result->fetch_assoc();
    
    // Check if requested dates are within property's available dates
    if ($check_in < $property['start_date'] || $check_out > $property['end_date']) {
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

function getPropertyOwnerId($conn, $property_id) {
    $sql = "SELECT user_id FROM properties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['user_id'];
    }
    
    return null;
}

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
        
        if (strpos($photos[0], 'http') !== 0) {
            $row['main_photo'] = !empty($photos[0]) ? '../properties/' . $photos[0] : '../images/default.jpg';
        } else {
            $row['main_photo'] = $photos[0];
        }

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

/**
 * Booking helper functions
 */

/**
 * Check if a property is available for the specified dates
 * 
 * @param mysqli $conn Database connection
 * @param int $property_id Property ID
 * @param string $check_in Check-in date (YYYY-MM-DD)
 * @param string $check_out Check-out date (YYYY-MM-DD)
 * @return bool True if available, false if not
 */
function isPropertyAvailableForBooking($conn, $property_id, $check_in, $check_out) {
    // First check if the property exists and is validated
    $property_sql = "SELECT * FROM properties WHERE id = ? AND validated = 1";
    $property_stmt = $conn->prepare($property_sql);
    $property_stmt->bind_param("i", $property_id);
    $property_stmt->execute();
    $property_result = $property_stmt->get_result();
    
    if ($property_result->num_rows === 0) {
        return false; // Property doesn't exist or is not validated
    }
    
    $property = $property_result->fetch_assoc();
    
    // Check if the requested dates are within the property's available dates
    $property_start = new DateTime($property['start_date']);
    $property_end = new DateTime($property['end_date']);
    $requested_start = new DateTime($check_in);
    $requested_end = new DateTime($check_out);
    
    if ($requested_start < $property_start || $requested_end > $property_end) {
        return false; // Requested dates are outside property's available dates
    }
    
    // Check if there are any overlapping bookings
    $booking_sql = "SELECT COUNT(*) as count FROM bookings 
                   WHERE property_id = ? 
                   AND status IN ('pending', 'confirmed') 
                   AND (
                       (check_in <= ? AND check_out >= ?) OR
                       (check_in <= ? AND check_out >= ?) OR
                       (check_in >= ? AND check_out <= ?)
                   )";
    
    $booking_stmt = $conn->prepare($booking_sql);
    $booking_stmt->bind_param("issssss", 
        $property_id, 
        $check_out, $check_in,  // Case 1: Existing booking overlaps with start
        $check_in, $check_out,  // Case 2: Existing booking overlaps with end
        $check_in, $check_out   // Case 3: Existing booking is within requested dates
    );
    
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    $booking_row = $booking_result->fetch_assoc();
    
    return $booking_row['count'] == 0; // Available if no overlapping bookings
}

/**
 * Calculate the number of nights between two dates
 * 
 * @param string $check_in Check-in date (YYYY-MM-DD)
 * @param string $check_out Check-out date (YYYY-MM-DD)
 * @return int Number of nights
 */
function calculateNights($check_in, $check_out) {
    $start = new DateTime($check_in);
    $end = new DateTime($check_out);
    $interval = $start->diff($end);
    return $interval->days;
}

/**
 * Calculate the total price for a booking
 * 
 * @param mysqli $conn Database connection
 * @param int $property_id Property ID
 * @param string $check_in Check-in date (YYYY-MM-DD)
 * @param string $check_out Check-out date (YYYY-MM-DD)
 * @param int $guests Number of guests
 * @return float|false Total price or false if property not found
 */
function calculateBookingPrice($conn, $property_id, $check_in, $check_out, $guests) {
    // Get property details
    $sql = "SELECT price FROM properties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false; // Property not found
    }
    
    $property = $result->fetch_assoc();
    $nights = calculateNights($check_in, $check_out);
    
    // Base price calculation
    $total_price = $property['price'] * $nights;
    
    // You could add additional pricing logic here, such as:
    // - Extra guest fees
    // - Weekend rates
    // - Seasonal pricing
    // - Discounts for longer stays
    
    return $total_price;
}

/**
 * Get property owner's bookings
 * 
 * @param mysqli $conn Database connection
 * @param int $owner_id Owner ID
 * @param string $status Optional booking status filter
 * @return array Array of bookings
 */
function getOwnerBookings($conn, $owner_id, $status = null) {
    $sql = "SELECT b.*, p.title, p.address, p.photos, u.username, u.email, u.phone
            FROM bookings b
            JOIN properties p ON b.property_id = p.id
            JOIN users u ON b.user_id = u.id
            WHERE p.user_id = ?";
    
    if ($status) {
        $sql .= " AND b.status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $owner_id, $status);
    } else {
        $sql .= " ORDER BY b.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $owner_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

/**
 * Update booking status
 * 
 * @param mysqli $conn Database connection
 * @param int $booking_id Booking ID
 * @param string $status New status ('confirmed', 'cancelled', 'completed')
 * @param int $user_id User ID (for authorization)
 * @return array Result of the operation
 */
// function updateBookingStatus($conn, $booking_id, $status, $user_id) {
//     // Check if user is authorized to update this booking
//     $auth_sql = "SELECT b.*, p.user_id as owner_id 
//                 FROM bookings b
//                 JOIN properties p ON b.property_id = p.id
//                 WHERE b.id = ?";
    
//     $auth_stmt = $conn->prepare($auth_sql);
//     $auth_stmt->bind_param("i", $booking_id);
//     $auth_stmt->execute();
//     $auth_result = $auth_stmt->get_result();
    
//     if ($auth_result->num_rows === 0) {
//         return [
//             'success' => false,
//             'message' => 'Réservation non trouvée.'
//         ];
//     }
    
//     $booking = $auth_result->fetch_assoc();
    
//     // Check if user is the guest or the property owner
//     $is_guest = ($booking['user_id'] == $user_id);
//     $is_owner = ($booking['owner_id'] == $user_id);
    
//     if (!$is_guest && !$is_owner) {
//         return [
//             'success' => false,
//             'message' => 'Vous n\'êtes pas autorisé à modifier cette réservation.'
//         ];
//     }
    
//     // Validate status transitions
//     $valid_transition = false;
    
//     if ($is_owner) {
//         // Owner can confirm or cancel pending bookings
//         if ($booking['status'] == 'pending' && ($status == 'confirmed' || $status == 'cancelled')) {
//             $valid_transition = true;
//         }
//     }
    
//     if ($is_guest) {
//         // Guest can only cancel their own bookings
//         if ($status == 'cancelled' && $booking['status'] != 'completed') {
//             $valid_transition = true;
//         }
//     }
    
//     if (!$valid_transition) {
//         return [
//             'success' => false,
//             'message' => 'Transition de statut non valide.'
//         ];
//     }
    
//     // Update booking status
//     $update_sql = "UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?";
//     $update_stmt = $conn->prepare($update_sql);
//     $update_stmt->bind_param("si", $status, $booking_id);
    
//     if ($update_stmt->execute()) {
//         // Create notification for the other party
//         $recipient_id = $is_owner ? $booking['user_id'] : $booking['owner_id'];
//         $title = '';
//         $message = '';
        
//         switch ($status) {
//             case 'confirmed':
//                 $title = 'Réservation confirmée';
//                 $message = 'Votre réservation a été confirmée par le propriétaire.';
//                 break;
//             case 'cancelled':
//                 $title = 'Réservation annulée';
//                 $message = $is_owner 
//                     ? 'Votre réservation a été annulée par le propriétaire.' 
//                     : 'Le voyageur a annulé sa réservation.';
//                 break;
//             case 'completed':
//                 $title = 'Réservation terminée';
//                 $message = 'Votre réservation est maintenant terminée.';
//                 break;
//         }
        
//         $notification_sql = "INSERT INTO notifications (
//             user_id, type, title, message, data, is_read, created_at
//         ) VALUES (
//             ?, 'booking_update', ?, ?, ?, 0, NOW()
//         )";
        
//         $notification_data = json_encode([
//             'booking_id' => $booking_id,
//             'property_id' => $booking['property_id'],
//             'status' => $status
//         ]);
        
//         $notification_stmt = $conn->prepare($notification_sql);
//         $notification_stmt->bind_param("isss", $recipient_id, $title, $message, $notification_data);
//         $notification_stmt->execute();
        
//         return [
//             'success' => true,
//             'message' => 'Statut de la réservation mis à jour avec succès.'
//         ];
//     } else {
//         return [
//             'success' => false,
//             'message' => 'Erreur lors de la mise à jour du statut: ' . $conn->error
//         ];
//     }
// }

/**
 * Get booking details by ID
 * 
 * @param mysqli $conn Database connection
 * @param int $booking_id Booking ID
 * @return array|null Booking details or null if not found
 */
function getBookingById($conn, $booking_id) {
    $sql = "SELECT b.*, p.title, p.address, p.photos, p.user_id as owner_id,
                  u.username, u.email, u.phone
            FROM bookings b
            JOIN properties p ON b.property_id = p.id
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

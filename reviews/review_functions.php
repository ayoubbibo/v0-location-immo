<?php
// Review helper functions

function addReview($conn, $property_id, $user_id, $booking_id, $rating, $comment) {
    // Check if user has already reviewed this property
    $check_sql = "SELECT id FROM reviews WHERE property_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $property_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        return ['success' => false, 'message' => 'Vous avez déjà laissé un avis pour cette propriété.'];
    }
    
    // Check if booking exists and is completed
    if ($booking_id) {
        $booking_sql = "SELECT id FROM bookings WHERE id = ? AND user_id = ? AND property_id = ? AND status = 'completed'";
        $booking_stmt = $conn->prepare($booking_sql);
        $booking_stmt->bind_param("iii", $booking_id, $user_id, $property_id);
        $booking_stmt->execute();
        $booking_result = $booking_stmt->get_result();
        
        if ($booking_result->num_rows === 0) {
            return ['success' => false, 'message' => 'Vous ne pouvez laisser un avis que pour une réservation terminée.'];
        }
    }
    
    // Insert review
    $sql = "INSERT INTO reviews (property_id, user_id, booking_id, rating, comment, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if ($booking_id) {
        $stmt->bind_param("iiiis", $property_id, $user_id, $booking_id, $rating, $comment);
    } else {
        $booking_id = null;
        $stmt->bind_param("iiiis", $property_id, $user_id, $booking_id, $rating, $comment);
    }
    
    if ($stmt->execute()) {
        $review_id = $conn->insert_id;
        
        // Update property rating
        updatePropertyRating($conn, $property_id);
        
        // Create notification for property owner
        $property_owner_id = getPropertyOwnerId($conn, $property_id);
        if ($property_owner_id) {
            createNotification($conn, $property_owner_id, 'review', 'Nouvel avis', 
                               'Vous avez reçu un nouvel avis pour votre propriété.', 
                               ['review_id' => $review_id]);
        }
        
        return ['success' => true, 'review_id' => $review_id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'avis: ' . $conn->error];
    }
}

function getPropertyOwnerId($conn, $property_id) {
    $sql = "SELECT user_id FROM annonce WHERE id = ?";
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

function updatePropertyRating($conn, $property_id) {
    $sql = "SELECT AVG(rating) as avg_rating, COUNT(id) as review_count 
            FROM reviews 
            WHERE property_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $avg_rating = $row['avg_rating'];
        $review_count = $row['review_count'];
        
        // Update property rating
        $update_sql = "UPDATE annonce SET rating = ?, review_count = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("dii", $avg_rating, $review_count, $property_id);
        $update_stmt->execute();
    }
}

function getPropertyReviews($conn, $property_id) {
    $sql = "SELECT r.*, u.username, u.profile_image 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.property_id = ? 
            ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        // Process profile image
        if (empty($row['profile_image'])) {
            $row['profile_image'] = '../images/default-avatar.png';
        } else {
            $row['profile_image'] = '../uploads/profiles/' . $row['profile_image'];
        }
        
        $reviews[] = $row;
    }
    
    return $reviews;
}

function getUserReviews($conn, $user_id) {
    $sql = "SELECT r.*, a.titre, a.adresse, a.photos 
            FROM reviews r 
            JOIN annonce a ON r.property_id = a.id 
            WHERE r.user_id = ? 
            ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        // Process photos
        $photos = explode(',', $row['photos']);
        $row['main_photo'] = !empty($photos[0]) ? '../annonces/' . $photos[0] : '../images/default.jpg';
        
        $reviews[] = $row;
    }
    
    return $reviews;
}

function getHostReviews($conn, $host_id) {
    $sql = "SELECT r.*, a.titre, a.adresse, a.photos, u.username, u.profile_image 
            FROM reviews r 
            JOIN annonce a ON r.property_id = a.id 
            JOIN users u ON r.user_id = u.id 
            WHERE a.user_id = ? 
            ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $host_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        // Process photos
        $photos = explode(',', $row['photos']);
        $row['main_photo'] = !empty($photos[0]) ? '../annonces/' . $photos[0] : '../images/default.jpg';
        
        // Process profile image
        if (empty($row['profile_image'])) {
            $row['profile_image'] = '../images/default-avatar.png';
        } else {
            $row['profile_image'] = '../uploads/profiles/' . $row['profile_image'];
        }
        
        $reviews[] = $row;
    }
    
    return $reviews;
}

function createNotification($conn, $user_id, $type, $title, $message, $data = []) {
    $data_json = json_encode($data);
    
    $sql = "INSERT INTO notifications (user_id, type, title, message, data, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $type, $title, $message, $data_json);
    
    return $stmt->execute();
}

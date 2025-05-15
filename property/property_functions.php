<?php
// Property helper functions

/**
 * Get all available properties with pagination
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of properties to return
 * @param int $offset Offset for pagination
 * @param bool $valid_only Only return validated properties
 * @return array Array of properties
 */
function getAllProperties($conn, $limit = 6, $offset = 0, $valid_only = true) {
    $sql = "SELECT * FROM properties";
    
    if ($valid_only) {
        $sql .= " WHERE validated = 1";
    }
    
    $sql .= " ORDER BY id DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
    } else {
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    
    return $properties;
}

/**
 * Get a specific property by ID
 * 
 * @param mysqli $conn Database connection
 * @param int $property_id Property ID
 * @return array|null Property data or null if not found
 */
function getPropertyById($conn, $property_id) {
    $sql = "SELECT a.*, u.username as owner_name, u.email as owner_email, u.phone as owner_phone 
            FROM properties a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE a.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Search properties by various criteria
 * 
 * @param mysqli $conn Database connection
 * @param array $criteria Search criteria
 * @return array Array of properties matching criteria
 */
function searchProperties($conn, $criteria) {
    $sql = "SELECT * FROM properties WHERE validated = 1";
    $types = "";
    $params = [];
    


    if (!empty($criteria['address'])) {
        $sql .= " AND (address LIKE ?)";
        $search_term = "%" . $criteria['address'] . "%";
        $types .= "sss";
        $params[] = $search_term;
    }
    
    // if (!empty($criteria['type_logement'])) {
    //     $sql .= " AND type_logement = ?";
    //     $types .= "s";
    //     $params[] = $criteria['type_logement'];
    // }
    
    // if (!empty($criteria['nombre_personnes']) && $criteria['nombre_personnes'] > 0) {
    //     $sql .= " AND capacite >= ?";
    //     $types .= "i";
    //     $params[] = $criteria['nombre_personnes'];
    // }
    
    // if (!empty($criteria['date_debut']) && !empty($criteria['date_fin'])) {
    //     // Check availability by excluding properties with overlapping bookings
    //     $sql .= " AND id NOT IN (
    //         SELECT property_id FROM reservations 
    //         WHERE (date_debut <= ? AND date_fin >= ?) 
    //         OR (date_debut <= ? AND date_fin >= ?) 
    //         OR (date_debut >= ? AND date_fin <= ?)
    //         AND statut IN ('confirmed', 'pending')
    //     )";
    //     $types .= "ssssss";
    //     $params[] = $criteria['date_fin'];
    //     $params[] = $criteria['date_debut'];
    //     $params[] = $criteria['date_debut'];
    //     $params[] = $criteria['date_fin'];
    //     $params[] = $criteria['date_debut'];
    //     $params[] = $criteria['date_fin'];
    // }
    
    // if (!empty($criteria['prix_min']) && $criteria['prix_min'] > 0) {
    //     $sql .= " AND tarif >= ?";
    //     $types .= "d";
    //     $params[] = $criteria['prix_min'];
    // }
    
    // if (!empty($criteria['prix_max']) && $criteria['prix_max'] > 0) {
    //     $sql .= " AND tarif <= ?";
    //     $types .= "d";
    //     $params[] = $criteria['prix_max'];
    // }
    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    
    return $properties;
}

/**
 * Get featured properties for homepage
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of properties to return
 * @return array Array of featured properties
 */
function getFeaturedProperties($conn, $limit = 6) {
    $sql = "SELECT * FROM properties 
            WHERE validated = 1 AND featured = 1 
            ORDER BY id DESC LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    
    return $properties;
}

/**
 * Get properties owned by a specific user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Array of properties owned by the user
 */
function getPropertiesByOwner($conn, $user_id) {
    $sql = "SELECT * FROM properties WHERE user_id = ? ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    
    return $properties;
}

/**
 * Get the main photo URL for a property
 * 
 * @param string $photos Comma-separated list of photos
 * @return string URL of the main photo
 */
function getMainPhotoUrl($photos) {
    $photo_array = explode(',', $photos);
    $photo = !empty($photo_array[0]) ? 'propertiess/' . $photo_array[0] : 'images/default.jpg';
    return $photo;
}

/**
 * Get all photo URLs for a property
 * 
 * @param string $photos Comma-separated list of photos
 * @return array Array of photo URLs
 */
function getAllPhotoUrls($photos) {
    $photo_array = explode(',', $photos);
    $photo_urls = [];
    
    foreach ($photo_array as $photo) {
        if (!empty($photo)) {
            $photo_urls[] = 'propertiess/' . $photo;
        }
    }
    
    if (empty($photo_urls)) {
        $photo_urls[] = 'images/default.jpg';
    }
    
    return $photo_urls;
}

/**
 * Add a property to user's favorites
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $property_id Property ID
 * @return array Result of the operation
 */
function addToFavorites($conn, $user_id, $property_id) {
    // Check if already in favorites
    $check_sql = "SELECT id FROM favoris WHERE user_id = ? AND property_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $property_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Already in favorites, remove it
        $delete_sql = "DELETE FROM favoris WHERE user_id = ? AND property_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $user_id, $property_id);
        
        if ($delete_stmt->execute()) {
            return ['success' => true, 'action' => 'removed'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la suppression des favoris: ' . $conn->error];
        }
    } else {
        // Not in favorites, add it
        $insert_sql = "INSERT INTO favoris (user_id, property_id, date_ajout) VALUES (?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $user_id, $property_id);
        
        if ($insert_stmt->execute()) {
            return ['success' => true, 'action' => 'added'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout aux favoris: ' . $conn->error];
        }
    }
}

/**
 * Remove a property from user's favorites
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $property_id Property ID
 * @return array Result of the operation
 */
function removeFromFavorites($conn, $user_id, $property_id) {
    $sql = "DELETE FROM favoris WHERE user_id = ? AND property_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $property_id);
    
    if ($stmt->execute()) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la suppression des favoris: ' . $conn->error];
    }
}

/**
 * Check if a property is in user's favorites
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $property_id Property ID
 * @return bool True if property is in favorites, false otherwise
 */
function isPropertyInFavorites($conn, $user_id, $property_id) {
    $sql = "SELECT id FROM favoris WHERE user_id = ? AND property_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Get all favorite properties for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Array of favorite properties
 */
function getUserFavorites($conn, $user_id) {
    $sql = "SELECT a.* 
            FROM properties a 
            JOIN favoris f ON a.id = f.property_id 
            WHERE f.user_id = ? 
            ORDER BY f.date_ajout DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    
    return $properties;
}

/**
 * Count total available properties
 * 
 * @param mysqli $conn Database connection
 * @param bool $valid_only Only count validated properties
 * @return int Total number of properties
 */
function countTotalProperties($conn, $valid_only = true) {
    $sql = "SELECT COUNT(*) as total FROM properties";
    
    if ($valid_only) {
        $sql .= " WHERE validated = 1";
    }
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

// /**
//  * Get property reviews
//  * 
//  * @param mysqli $conn Database connection
//  * @param int $property_id Property ID
//  * @return array Array of reviews
//  */
// function getPropertyReviews($conn, $property_id) {
//     $sql = "SELECT a.*, u.username, u.profile_image 
//             FROM avis a 
//             JOIN users u ON a.user_id = u.id 
//             WHERE a.property_id = ? 
//             ORDER BY a.date_avis DESC";
    
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("i", $property_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
    
//     $reviews = [];
//     while ($row = $result->fetch_assoc()) {
//         $reviews[] = $row;
//     }
    
//     return $reviews;
// }

/**
 * Get average rating for a property
 * 
 * @param mysqli $conn Database connection
 * @param int $property_id Property ID
 * @return float Average rating
 */
function getAverageRating($conn, $property_id) {
    $sql = "SELECT AVG(note) as average FROM avis WHERE property_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return round($row['average'] ?? 0, 1);
}

/**
 * Check if property is available for specific dates
 * 
 * @param mysqli $conn Database connection
 * @param int $property_id Property ID
 * @param string $start_date Start date (YYYY-MM-DD)
 * @param string $end_date End date (YYYY-MM-DD)
 * @return bool True if property is available, false otherwise
 */
function isPropertyAvailable($conn, $property_id, $start_date, $end_date) {
    $sql = "SELECT COUNT(*) as count FROM reservations 
            WHERE property_id = ? 
            AND statut IN ('confirmed', 'pending')
            AND (
                (date_debut <= ? AND date_fin >= ?) 
                OR (date_debut <= ? AND date_fin >= ?) 
                OR (date_debut >= ? AND date_fin <= ?)
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $property_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] === 0;
}

/**
 * Calculate total price for a booking
 * 
 * @param array $property Property data
 * @param string $start_date Start date (YYYY-MM-DD)
 * @param string $end_date End date (YYYY-MM-DD)
 * @return float Total price
 */
function calculateTotalPrice($property, $start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $nights = $interval->days;
    
    return $property['tarif'] * $nights;
}

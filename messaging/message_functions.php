<?php
// Messaging helper functions

function sendMessage($conn, $sender_id, $receiver_id, $message, $booking_id = null) {
    $sql = "INSERT INTO messages (sender_id, receiver_id, message, booking_id, created_at, is_read) 
            VALUES (?, ?, ?, ?, NOW(), 0)";
    
    $stmt = $conn->prepare($sql);
    
    if ($booking_id) {
        $stmt->bind_param("iisi", $sender_id, $receiver_id, $message, $booking_id);
    } else {
        $booking_id = null;
        $stmt->bind_param("iisi", $sender_id, $receiver_id, $message, $booking_id);
    }
    
    if ($stmt->execute()) {
        $message_id = $conn->insert_id;
        
        // Create notification for receiver
        createNotification($conn, $receiver_id, 'message', 'Nouveau message', 
                          'Vous avez reçu un nouveau message.', 
                          ['message_id' => $message_id]);
        
        return ['success' => true, 'message_id' => $message_id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'envoi du message: ' . $conn->error];
    }
}

function getConversations($conn, $user_id) {
    $sql = "SELECT 
                c.id as conversation_id,
                c.user1_id,
                c.user2_id,
                c.last_message_id,
                c.updated_at,
                m.message as last_message,
                m.created_at as last_message_time,
                m.sender_id as last_message_sender,
                CASE 
                    WHEN c.user1_id = ? THEN c.user2_id
                    ELSE c.user1_id
                END as other_user_id,
                u.username as other_user_name,
                u.profile_image as other_user_image,
                (SELECT COUNT(*) FROM messages 
                 WHERE conversation_id = c.id 
                 AND receiver_id = ? 
                 AND is_read = 0) as unread_count
            FROM conversations c
            JOIN messages m ON c.last_message_id = m.id
            JOIN users u ON (CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END) = u.id
            WHERE c.user1_id = ? OR c.user2_id = ?
            ORDER BY c.updated_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        // Process profile image
        if (empty($row['other_user_image'])) {
            $row['other_user_image'] = '../images/default-avatar.png';
        } else {
            $row['other_user_image'] = '../uploads/profiles/' . $row['other_user_image'];
        }
        
        $conversations[] = $row;
    }
    
    return $conversations;
}

function getOrCreateConversation($conn, $user1_id, $user2_id) {
    // Check if conversation already exists
    $check_sql = "SELECT id FROM conversations 
                 WHERE (user1_id = ? AND user2_id = ?) 
                 OR (user1_id = ? AND user2_id = ?)";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iiii", $user1_id, $user2_id, $user2_id, $user1_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        return $row['id'];
    }
    
    // Create new conversation
    $create_sql = "INSERT INTO conversations (user1_id, user2_id, created_at, updated_at) 
                  VALUES (?, ?, NOW(), NOW())";
    
    $create_stmt = $conn->prepare($create_sql);
    $create_stmt->bind_param("ii", $user1_id, $user2_id);
    
    if ($create_stmt->execute()) {
        return $conn->insert_id;
    }
    
    return null;
}

function getMessages($conn, $conversation_id, $user_id) {
    // Check if user is part of this conversation
    $check_sql = "SELECT id FROM conversations 
                 WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        return ['success' => false, 'message' => 'Vous n\'avez pas accès à cette conversation.'];
    }
    
    // Get messages
    $sql = "SELECT m.*, 
                  u.username as sender_name, 
                  u.profile_image as sender_image
           FROM messages m
           JOIN users u ON m.sender_id = u.id
           WHERE m.conversation_id = ?
           ORDER BY m.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        // Process profile image
        if (empty($row['sender_image'])) {
            $row['sender_image'] = '../images/default-avatar.png';
        } else {
            $row['sender_image'] = '../uploads/profiles/' . $row['sender_image'];
        }
        
        $messages[] = $row;
    }
    
    // Mark messages as read
    $update_sql = "UPDATE messages 
                  SET is_read = 1 
                  WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $conversation_id, $user_id);
    $update_stmt->execute();
    
    return ['success' => true, 'messages' => $messages];
}

function getConversationDetails($conn, $conversation_id, $user_id) {
    $sql = "SELECT c.*,
                  CASE 
                      WHEN c.user1_id = ? THEN c.user2_id
                      ELSE c.user1_id
                  END as other_user_id,
                  u.username as other_user_name,
                  u.profile_image as other_user_image
           FROM conversations c
           JOIN users u ON (CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END) = u.id
           WHERE c.id = ? AND (c.user1_id = ? OR c.user2_id = ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $user_id, $user_id, $conversation_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $conversation = $result->fetch_assoc();
        
        // Process profile image
        if (empty($conversation['other_user_image'])) {
            $conversation['other_user_image'] = '../images/default-avatar.png';
        } else {
            $conversation['other_user_image'] = '../uploads/profiles/' . $conversation['other_user_image'];
        }
        
        return $conversation;
    }
    
    return null;
}

// function createNotification($conn, $user_id, $type, $title, $message, $data = []) {
//     $data_json = json_encode($data);
    
//     $sql = "INSERT INTO notifications (user_id, type, title, message, data, created_at) 
//             VALUES (?, ?, ?, ?, ?, NOW())";
    
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("issss", $user_id, $type, $title, $message, $data_json);
    
//     return $stmt->execute();
// }

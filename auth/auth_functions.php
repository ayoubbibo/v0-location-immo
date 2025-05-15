<?php
// Authentication helper functions

function registerUser($conn, $username, $email, $password, $phone, $user_type = 'guest') {
    // Check if email already exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (username, email, password, phone, user_type, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $phone, $user_type);
    
    if ($stmt->execute()) {
        return ['success' => true, 'user_id' => $conn->insert_id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'inscription: ' . $conn->error];
    }
}

function loginUser($conn, $email, $password) {
    $sql = "SELECT id, username, email, password, user_type, profile_image FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Start session and store user data
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['profile_image'] = $user['profile_image'];
            $_SESSION['logged_in'] = true;
            
            // Update last login time
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            return ['success' => true, 'user' => $user];
        }
    }
    
    return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
}

function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Redirect to home page
    header("Location: ../index.php");
    exit;
}

function getUserData($conn, $user_id) {
    if (!$conn) {
        error_log("Database connection is null in getUserData");
        return null;
    }
    
    $sql = "SELECT id, username, email, phone, user_type, profile_image, created_at, last_login 
            FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

function updateUserProfile($conn, $user_id, $username, $phone, $profile_image = null) {
    $sql = "UPDATE users SET username = ?, phone = ?";
    $types = "ss";
    $params = [$username, $phone];
    
    if ($profile_image) {
        $sql .= ", profile_image = ?";
        $types .= "s";
        $params[] = $profile_image;
    }
    
    $sql .= " WHERE id = ?";
    $types .= "i";
    $params[] = $user_id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour du profil: ' . $conn->error];
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../logins/connexion.php");
        exit;
    }
}

function requireHostPrivileges() {
    if (!isLoggedIn() || $_SESSION['user_type'] !== 'host') {
        header("Location: ../index.php");
        exit;
    }
}

function requireAdminPrivileges() {
    if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
        header("Location: ../index.php");
        exit;
    }
}

function updatePassword($conn, $user_id, $current_password, $new_password) {
    // First verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            // Current password is correct, update to new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                return ['success' => true];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe: ' . $conn->error];
            }
        } else {
            return ['success' => false, 'message' => 'Le mot de passe actuel est incorrect.'];
        }
    }
    
    return ['success' => false, 'message' => 'Utilisateur non trouvé.'];
}

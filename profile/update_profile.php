<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get database connection
$conn = getDbConnection();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate current password
    $password_check = updatePassword($conn, $user_id, $current_password, '');
    
    if (!$password_check['success']) {
        // Current password is incorrect
        $_SESSION['error'] = $password_check['message'];
        header("Location: profile_dashboard.php");
        exit;
    }
    
    // Handle profile image upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profiles/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        // Check if file is an image
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image = $target_file;
            } else {
                $_SESSION['error'] = "Erreur lors du téléchargement de l'image.";
                header("Location: profile_dashboard.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Le fichier n'est pas une image valide.";
            header("Location: profile_dashboard.php");
            exit;
        }
    }
    
    // Update profile
    $update_result = updateUserProfile($conn, $user_id, $username, $phone, $profile_image);
    
    if (!$update_result['success']) {
        $_SESSION['error'] = $update_result['message'];
        header("Location: profile_dashboard.php");
        exit;
    }
    
    // Update password if provided
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            header("Location: profile_dashboard.php");
            exit;
        }
        
        $password_update = updatePassword($conn, $user_id, $current_password, $new_password);
        
        if (!$password_update['success']) {
            $_SESSION['error'] = $password_update['message'];
            header("Location: profile_dashboard.php");
            exit;
        }
    }
    
    // Update session data
    $_SESSION['username'] = $username;
    if ($profile_image) {
        $_SESSION['profile_image'] = $profile_image;
    }
    
    $_SESSION['success'] = "Profil mis à jour avec succès.";
    header("Location: profile_dashboard.php");
    exit;
}

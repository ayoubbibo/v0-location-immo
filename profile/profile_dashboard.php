<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get database connection
$conn = getDbConnection();

// Get user data
$user = getUserData($conn, $user_id);

// Get user bookings
$bookings = getUserBookings($conn, $user_id);

// Get user favorites
$favorites = getUserFavorites($conn, $user_id);

// Get user reviews
$reviews = getUserReviews($conn, $user_id);

// Get user messages
$messages = getUserMessages($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - MN Home DZ</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
        }
        
        .dashboard-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .tab.active {
            border-bottom: 2px solid #4CAF50;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .booking-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .edit-profile-form {
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .message-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .unread {
            font-weight: bold;
        }
        
        .message-date {
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <?php if ($user['profile_image']): ?>
                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Photo de profil" class="profile-image">
            <?php else: ?>
                <img src="../images/default-profile.png" alt="Photo de profil par défaut" class="profile-image">
            <?php endif; ?>
            
            <div>
                <h1>Bonjour, <?php echo htmlspecialchars($user['username']); ?></h1>
                <p>Membre depuis <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                <?php if ($user['user_type'] === 'host'): ?>
                    <a href="../host/dashboard.php" class="btn btn-primary">Accéder à mon espace hôte</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-tabs">
            <div class="tab active" data-tab="reservations">Mes réservations</div>
            <div class="tab" data-tab="favorites">Mes favoris</div>
            <div class="tab" data-tab="reviews">Mes avis</div>
            <div class="tab" data-tab="messages">Messages</div>
            <div class="tab" data-tab="profile">Modifier mon profil</div>
        </div>
        
        <div id="reservations" class="tab-content active">
            <h2>Mes réservations</h2>
            <?php if (empty($bookings)): ?>
                <p>Vous n'avez pas encore de réservations.</p>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($booking['property_title']); ?></h3>
                            <p>
                                <strong>Dates:</strong> 
                                <?php echo date('d/m/Y', strtotime($booking['date_debut'])); ?> - 
                                <?php echo date('d/m/Y', strtotime($booking['date_fin'])); ?>
                            </p>
                            <p><strong>Prix total:</strong> <?php echo number_format($booking['prix_total'], 2); ?> DA</p>
                            <p>
                                <span class="booking-status status-<?php echo strtolower($booking['statut']); ?>">
                                    <?php echo getStatusLabel($booking['statut']); ?>
                                </span>
                            </p>
                            <a href="../booking/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">Voir les détails</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="favorites" class="tab-content">
            <h2>Mes favoris</h2>
            <?php if (empty($favorites)): ?>
                <p>Vous n'avez pas encore de favoris.</p>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($favorites as $property): ?>
                        <div class="card">
                            <img src="<?php echo getMainPhotoUrl($property); ?>" alt="<?php echo htmlspecialchars($property['titre']); ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                            <h3><?php echo htmlspecialchars($property['titre']); ?></h3>
                            <p><?php echo htmlspecialchars($property['adresse']); ?></p>
                            <p><strong>Prix:</strong> <?php echo number_format($property['tarif'], 2); ?> DA/nuit</p>
                            <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                                <a href="../detail_bien.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">Voir les détails</a>
                                <button class="btn remove-favorite" data-id="<?php echo $property['id']; ?>">Retirer des favoris</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="reviews" class="tab-content">
            <h2>Mes avis</h2>
            <?php if (empty($reviews)): ?>
                <p>Vous n'avez pas encore laissé d'avis.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($review['property_title']); ?></h3>
                        <div>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $review['note']): ?>
                                    <i class="fas fa-star" style="color: gold;"></i>
                                <?php else: ?>
                                    <i class="far fa-star" style="color: gold;"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <p><?php echo htmlspecialchars($review['commentaire']); ?></p>
                        <p class="message-date">Posté le <?php echo date('d/m/Y', strtotime($review['date_avis'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="messages" class="tab-content">
            <h2>Messages</h2>
            <?php if (empty($messages)): ?>
                <p>Vous n'avez pas de messages.</p>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="card message-item <?php echo $message['lu'] ? '' : 'unread'; ?>">
                        <div>
                            <h3><?php echo htmlspecialchars($message['sujet']); ?></h3>
                            <p>De: <?php echo htmlspecialchars($message['sender_name']); ?></p>
                        </div>
                        <div>
                            <p class="message-date"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></p>
                            <a href="../messaging/conversation.php?id=<?php echo $message['conversation_id']; ?>" class="btn btn-primary">Voir</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="profile" class="tab-content">
            <h2>Modifier mon profil</h2>
            <form class="edit-profile-form" action="update_profile.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    <small>L'email ne peut pas être modifié.</small>
                </div>
                
                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="profile_image">Photo de profil</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel (requis pour les modifications)</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </form>
        </div>
    </div>
    
    <?php include_once '../includes/footer.php'; ?>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and content
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Remove from favorites
        document.querySelectorAll('.remove-favorite').forEach(button => {
            button.addEventListener('click', function() {
                const propertyId = this.getAttribute('data-id');
                
                fetch('../ajax/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'property_id=' + propertyId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the card from the UI
                        this.closest('.card').remove();
                        
                        // If no more favorites, show message
                        if (document.querySelectorAll('#favorites .card').length === 0) {
                            document.getElementById('favorites').innerHTML = '<h2>Mes favoris</h2><p>Vous n\'avez pas encore de favoris.</p>';
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Helper function to get status label
function getStatusLabel($status) {
    switch ($status) {
        case 'confirmed':
            return 'Confirmée';
        case 'pending':
            return 'En attente';
        case 'cancelled':
            return 'Annulée';
        case 'completed':
            return 'Terminée';
        default:
            return $status;
    }
}

// Helper functions for the dashboard
function getUserBookings($conn, $user_id) {
    $sql = "SELECT r.*, a.titre as property_title 
            FROM reservations r 
            JOIN annonce a ON r.annonce_id = a.id 
            WHERE r.user_id = ? 
            ORDER BY r.date_debut DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

function getUserReviews($conn, $user_id) {
    $sql = "SELECT r.*, a.titre as property_title 
            FROM avis r 
            JOIN annonce a ON r.annonce_id = a.id 
            WHERE r.user_id = ? 
            ORDER BY r.date_avis DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    return $reviews;
}

function getUserMessages($conn, $user_id) {
    $sql = "SELECT m.*, c.id as conversation_id, 
            CASE 
                WHEN c.user_id = ? THEN h.username 
                ELSE u.username 
            END as sender_name
            FROM messages m 
            JOIN conversations c ON m.conversation_id = c.id 
            JOIN users u ON c.user_id = u.id 
            JOIN users h ON c.host_id = h.id 
            WHERE (c.user_id = ? OR c.host_id = ?) 
            AND m.sender_id != ? 
            AND m.id = (
                SELECT MAX(id) FROM messages 
                WHERE conversation_id = c.id
            )
            ORDER BY m.date_envoi DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    return $messages;
}

function getMainPhotoUrl($property) {
    $photos = explode(',', $property['photos']);
    $photo = !empty($photos[0]) ? '../annonces/' . $photos[0] : '../images/default.jpg';
    return $photo;
}
?>

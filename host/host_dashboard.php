<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../booking/booking_functions.php';
require_once '../reviews/review_functions.php';



// Get database connection
$conn = getDbConnection();

// Require host privileges
requireHostPrivileges();

// Get user data
$user = getUserData($conn, $_SESSION['user_id']);

if (!$user) {
    header("Location: ../logins/logout.php");
    exit;
}

// Get host properties
$properties_sql = "SELECT * FROM properties WHERE user_id = ? ORDER BY created_at DESC";
$properties_stmt = $conn->prepare($properties_sql);
$properties_stmt->bind_param("i", $_SESSION['user_id']);
$properties_stmt->execute();
$properties_result = $properties_stmt->get_result();
$properties = [];
while ($row = $properties_result->fetch_assoc()) {
    // Process photos
    $photos = explode(',', $row['photos']);

    if (strpos($photos[0], 'http') !== 0) {
        $row['main_photo'] = !empty($photos[0]) ? '../properties/' . $photos[0] : '../images/default.jpg';
    } else {
        $row['main_photo'] = $photos[0];
    }
    
    $properties[] = $row;
}

// Get host bookings
$bookings = getHostBookings($conn, $_SESSION['user_id']);

// Get host reviews
$reviews = getHostReviews($conn, $_SESSION['user_id']);

// Get unread messages count
$unread_sql = "SELECT COUNT(*) as unread_count 
               FROM messages m 
               JOIN conversations c ON m.conversation_id = c.id 
               WHERE m.receiver_id = ? AND m.is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("i", $_SESSION['user_id']);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_row = $unread_result->fetch_assoc();
$unread_messages = $unread_row['unread_count'];

// Get pending bookings count
$pending_bookings = 0;
foreach ($bookings as $booking) {
    if ($booking['status'] === 'pending') {
        $pending_bookings++;
    }
}

// Get total earnings
$earnings_sql = "SELECT SUM(b.total_price) as total_earnings 
                FROM bookings b 
                JOIN properties a ON b.property_id = a.id 
                WHERE a.user_id = ? AND b.status IN ('confirmed', 'completed')";
$earnings_stmt = $conn->prepare($earnings_sql);
$earnings_stmt->bind_param("i", $_SESSION['user_id']);
$earnings_stmt->execute();
$earnings_result = $earnings_stmt->get_result();
$earnings_row = $earnings_result->fetch_assoc();
$total_earnings = $earnings_row['total_earnings'] ?? 0;

// Get notifications
$notifications_sql = "SELECT * FROM notifications 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC 
                     LIMIT 5";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("i", $_SESSION['user_id']);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();
$notifications = [];
while ($row = $notifications_result->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Hôte - MN Home DZ</title>
    <link rel="stylesheet" href="./style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

    </style>
</head>

<body>
    <nav class="nav-barre">
        <div class="logo-container">
            <a href="index.php">
                <img class="Logo" src="../images/LogoBlack.png" alt="Logo" />
            </a>
        </div>


        <div>
            <?php if (isLoggedIn()): ?>
                <a href="../profile/dashboard.php"><button class="button1">Mon Compte</button></a>
                <a href="../logins/logout.php"><button class="button2">Déconnexion</button></a>
            <?php else: ?>
                <a href="../logins/connexion.php"><button class="button1">Connexion</button></a>
                <a href="../logins/formulaire.php"><button class="button2">Créer un compte</button></a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="user-welcome">
                <img src="<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">

                <div class="user-info">
                    <h1>Espace Hôte</h1>
                    <span class="user-type">Bienvenue, <?php echo htmlspecialchars($user['username']); ?></span>
                </div>
            </div>

            <div class="dashboard-actions">
                <a href="add-property.php" class="btn-action btn-primary">
                    <i class="fas fa-plus"></i> Ajouter une propriété
                </a>

                <a href="../profile/profile_dashboard.php" class="btn-action btn-secondary">
                    <i class="fas fa-user"></i> Espace voyageur
                </a>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-main">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Statistiques</h2>
                    </div>

                    <div class="card-content">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo count($properties); ?></div>
                                <div class="stat-label">Propriétés</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-value"><?php echo count($bookings); ?></div>
                                <div class="stat-label">Réservations</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-value"><?php echo $pending_bookings; ?></div>
                                <div class="stat-label">En attente</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-value"><?php echo number_format($total_earnings); ?> DA</div>
                                <div class="stat-label">Revenus</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Mes propriétés</h2>
                        <a href="properties.php" class="card-action">Voir tout</a>
                    </div>

                    <div class="card-content">
                        <?php if (empty($properties)): ?>
                            <div class="empty-state">
                                <i class="fas fa-home"></i>
                                <h3>Aucune propriété</h3>
                                <p>Vous n'avez pas encore ajouté de propriété.</p>
                                <a href="add-property.php" class="btn-action btn-primary" style="margin-top: 15px;">
                                    <i class="fas fa-plus"></i> Ajouter une propriété
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="property-list">
                                <?php
                                // Show only the 3 most recent properties
                                $recent_properties = array_slice($properties, 0, 3);
                                foreach ($recent_properties as $property):

                                    // Determine status class and text
                                    $status_class = $property['validated'] ? 'status-active' : 'status-inactive';
                                    $status_text = $property['validated'] ? 'Active' : 'Inactive';
                                ?>
                                    <div class="property-item">
                                        <img src="<?php echo htmlspecialchars($property['main_photo']); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" class="property-image">

                                        <div class="property-details">
                                            <div class="property-title"><?php echo htmlspecialchars($property['title']); ?></div>

                                            <div class="property-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($property['address']); ?>
                                            </div>

                                            <span class="property-status <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>

                                            <div class="property-price">
                                                <?php echo number_format($property['price']); ?> DA / nuit
                                            </div>
                                        </div>

                                        <div class="property-actions" style="display: flex; flex-direction: column; justify-content: center; padding: 0 15px;">
                                            <a href="../property/property_details.php?id=<?php echo $property['id']; ?>" class="btn-action btn-secondary" style="margin-bottom: 10px;">
                                                <i class="fas fa-eye"></i> Voir
                                            </a>

                                            <a href="../property/edit_property.php?id=<?php echo $property['id']; ?>" class="btn-action btn-primary">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Réservations récentes</h2>
                        <a href="bookings.php" class="card-action">Voir tout</a>
                    </div>

                    <div class="card-content">
                        <?php if (empty($bookings)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-alt"></i>
                                <h3>Aucune réservation</h3>
                                <p>Vous n'avez pas encore reçu de réservation.</p>
                            </div>
                        <?php else: ?>
                            <div class="booking-list">
                                <?php
                                // Show only the 3 most recent bookings
                                $recent_bookings = array_slice($bookings, 0, 3);
                                foreach ($recent_bookings as $booking):

                                    // Determine status class and text
                                    $status_class = '';
                                    $status_text = '';

                                    switch ($booking['status']) {
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            $status_text = 'En attente';
                                            break;
                                        case 'confirmed':
                                            $status_class = 'status-confirmed';
                                            $status_text = 'Confirmée';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'status-cancelled';
                                            $status_text = 'Annulée';
                                            break;
                                        case 'completed':
                                            $status_class = 'status-completed';
                                            $status_text = 'Terminée';
                                            break;
                                    }
                                ?>
                                    <div class="booking-item">
                                        <img src="<?php echo htmlspecialchars($booking['main_photo']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>" class="booking-image">

                                        <div class="booking-details">
                                            <div class="booking-title"><?php echo htmlspecialchars($booking['title']); ?></div>

                                            <div class="booking-location">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($booking['username']); ?> (<?php echo htmlspecialchars($booking['email']); ?>)
                                            </div>

                                            <div class="booking-dates">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('d/m/Y', strtotime($booking['check_in'])); ?> -
                                                <?php echo date('d/m/Y', strtotime($booking['check_out'])); ?>
                                                (<?php echo $booking['nights']; ?> nuits)
                                            </div>

                                            <span class="booking-status <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>

                                            <div class="booking-price">
                                                <?php echo number_format($booking['total_price']); ?> DA
                                            </div>
                                        </div>

                                        <div class="booking-actions" style="display: flex; flex-direction: column; justify-content: center; padding: 0 15px;">
                                            <a href="../booking/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn-action btn-secondary">
                                                <i class="fas fa-eye"></i> Détails
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-sidebar">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Notifications</h2>
                    </div>

                    <div class="card-content">
                        <?php if (empty($notifications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bell"></i>
                                <h3>Aucune notification</h3>
                                <p>Vous n'avez pas de nouvelles notifications.</p>
                            </div>
                        <?php else: ?>
                            <div class="notification-list">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item">
                                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                        <div class="notification-time">
                                            <?php
                                            $notification_time = strtotime($notification['created_at']);
                                            $now = time();
                                            $diff = $now - $notification_time;

                                            if ($diff < 60) {
                                                echo 'À l\'instant';
                                            } elseif ($diff < 3600) {
                                                echo 'Il y a ' . floor($diff / 60) . ' min';
                                            } elseif ($diff < 86400) {
                                                echo 'Il y a ' . floor($diff / 3600) . ' h';
                                            } elseif ($diff < 172800) {
                                                echo 'Hier';
                                            } else {
                                                echo date('d/m/Y', $notification_time);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Avis récents</h2>
                        <a href="reviews.php" class="card-action">Voir tout</a>
                    </div>

                    <div class="card-content">
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state">
                                <i class="fas fa-star"></i>
                                <h3>Aucun avis</h3>
                                <p>Vous n'avez pas encore reçu d'avis.</p>
                            </div>
                        <?php else: ?>
                            <div class="review-list">
                                <?php
                                // Show only the 3 most recent reviews
                                $recent_reviews = array_slice($reviews, 0, 3);
                                foreach ($recent_reviews as $review):
                                ?>
                                    <div class="review-item">
                                        <img src="<?php echo htmlspecialchars($review['profile_image']); ?>" alt="<?php echo htmlspecialchars($review['username']); ?>" class="review-image" style="border-radius: 50%; object-fit: cover;">

                                        <div class="review-details">
                                            <div class="review-title"><?php echo htmlspecialchars($review['username']); ?></div>

                                            <div class="review-location">
                                                <i class="fas fa-home"></i>
                                                <?php echo htmlspecialchars($review['title']); ?>
                                            </div>

                                            <div class="review-date">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                            </div>

                                            <div class="review-rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $review['rating']) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Liens rapides</h2>
                    </div>

                    <div class="card-content">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 10px;">
                                <a href="../messaging/inbox.php" style="display: flex; align-items: center; gap: 10px; color: #4b5563; text-decoration: none;">
                                    <i class="fas fa-envelope"></i>
                                    <span>Messagerie</span>
                                    <?php if ($unread_messages > 0): ?>
                                        <span style="background-color: #5D76A9; color: white; font-size: 12px; padding: 2px 6px; border-radius: 10px;"><?php echo $unread_messages; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="add-property.php" style="display: flex; align-items: center; gap: 10px; color: #4b5563; text-decoration: none;">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Ajouter une propriété</span>
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="bookings.php" style="display: flex; align-items: center; gap: 10px; color: #4b5563; text-decoration: none;">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Gérer les réservations</span>
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="../profile/edit-profile.php" style="display: flex; align-items: center; gap: 10px; color: #4b5563; text-decoration: none;">
                                    <i class="fas fa-user-edit"></i>
                                    <span>Modifier le profil</span>
                                </a>
                            </li>
                            <li>
                                <a href="../profile/profile_dashboard.php" style="display: flex; align-items: center; gap: 10px; color: #4b5563; text-decoration: none;">
                                    <i class="fas fa-user"></i>
                                    <span>Espace voyageur</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
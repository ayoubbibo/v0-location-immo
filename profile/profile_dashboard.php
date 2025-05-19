<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../booking/booking_functions.php';
require_once '../reviews/review_functions.php';

// Require login
requireLogin();

// Get database connection
$conn = getDbConnection();


// Get user data
$user = getUserData($conn, $_SESSION['user_id']);

if (!$user) {
    header("Location: ../logins/logout.php");
    exit;
}

// Get user bookings
$bookings = getUserBookings($conn, $_SESSION['user_id']);

// Get user reviews
$reviews = getUserReviews($conn, $_SESSION['user_id']);

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
    <title>Tableau de bord - MN Home DZ</title>
    <link rel="stylesheet" href="./style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="images/Logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

</head>

<body>
    <nav class="nav-barre">
        <div class="logo-container">
            <a href="../index.php">
                <img class="Logo" src="../images/LogoBlack.png" alt="Logo" />
            </a>
        </div>
        <div>
            <a href="../index.php"><button class="button1">Acceuil</button></a>
            <a href="../logins/logout.php"><button class="button2">Déconnexion</button></a>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="user-welcome">
                <img src="<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">


                <div class="user-info">
                    <h1>Bonjour, <?php echo htmlspecialchars($user['username']); ?></h1>
                    <span class="user-type">
                        <?php
                        switch ($user['user_type']) {
                            case 'guest':
                                echo 'Voyageur';
                                break;
                            case 'host':
                                echo 'Hôte';
                                break;
                            case 'admin':
                                echo 'Administrateur';
                                break;
                            default:
                                echo 'Utilisateur';
                        }
                        ?>
                    </span>
                </div>
            </div>

            <div class="dashboard-actions">
                <a href="edit_profile.php" class="btn-action btn-secondary">
                    <i class="fas fa-user-edit"></i> Modifier le profil
                </a>

                <?php if ($user['user_type'] === 'host'): ?>
                    <a href="../host/host_dashboard.php" class="btn-action btn-primary">
                        <i class="fas fa-home"></i> Espace hôte
                    </a>
                <?php endif; ?>

                <?php if ($user['user_type'] === 'admin'): ?>
                    <a href="../admin/dashboard.php" class="btn-action btn-primary">
                        <i class="fas fa-cog"></i> Administration
                    </a>
                <?php endif; ?>
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
                            <a class="stat-card" href="#Reservations">
                                <div class="stat-value"><?php echo count($bookings); ?></div>
                                <div class="stat-label">Réservations</div>
                            </a>

                            <a class="stat-card" href="#Reviews">
                                <div class="stat-value"><?php echo count($reviews); ?></div>
                                <div class="stat-label">Avis</div>
                            </a>
                            
                            <a class="stat-card">
                                <div class="stat-value"><?php echo $unread_messages; ?></div>
                                <div class="stat-label">Messages non lus</div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" id="Reservations">
                    <div class="card-header" >
                        <h2 class="card-title" >Mes réservations</h2>
                        <a href="bookings.php" class="card-action">Voir tout</a>
                    </div>

                    <div class="card-content">
                        <?php if (empty($bookings)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-alt"></i>
                                <h3>Aucune réservation</h3>
                                <p>Vous n'avez pas encore effectué de réservation.</p>
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
                                        <img src="<?php echo htmlspecialchars($booking['photos']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>" class="booking-image">

                                        <div class="booking-details">
                                            <div class="booking-title"><?php echo htmlspecialchars($booking['title']); ?></div>

                                            <div class="booking-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($booking['address']); ?>
                                            </div>

                                            <div class="booking-dates">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('d/m/Y', strtotime($booking['check_in'])); ?> -
                                                <?php echo date('d/m/Y', strtotime($booking['check_out'])); ?>
                                                (<?php echo $booking['nights']; ?> nuits)
                                            </div>

                                            <div class="booking-price">
                                                <?php echo number_format($booking['total_price']); ?> DA
                                            </div>

                                            <span class="booking-status <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </div>

                                        <div class="booking-actions" style="display: flex; flex-direction: column; justify-content: center; padding: 0 15px;">
                                            <a href="../booking/booking_confirmation.php?id=<?= $booking['id'] ?>" class="btn-action btn-secondary" style="margin-bottom: 10px;">
                                                <i class="fas fa-eye"></i> Détails
                                            </a>

                                            <?php if ($booking['status'] === 'completed'): ?>
                                                <a href="../reviews/add-review.php?property_id=<?php echo $booking['property_id']; ?>&booking_id=<?php echo $booking['id']; ?>" class="btn-action btn-primary">
                                                    <i class="fas fa-star"></i> Avis
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card" id="Reviews">
                    <div class="card-header">
                        <h2 class="card-title">Mes avis</h2>
                        <a href="reviews.php" class="card-action">Voir tout</a>
                    </div>

                    <div class="card-content">
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state">
                                <i class="fas fa-star"></i>
                                <h3>Aucun avis</h3>
                                <p>Vous n'avez pas encore laissé d'avis.</p>
                            </div>
                        <?php else: ?>
                            <div class="review-list">
                                <?php
                                // Show only the 3 most recent reviews
                                $recent_reviews = array_slice($reviews, 0, 3);
                                foreach ($recent_reviews as $review):
                                ?>
                                    <div class="review-item">
                                        <img src="<?php echo htmlspecialchars($review['photos']); ?>" alt="<?php echo htmlspecialchars($review['title']); ?>" class="review-image">

                                        <div class="review-details">
                                            <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>

                                            <div class="review-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($review['address']); ?>
                                            </div>

                                            <div class="review-date">
                                                <i class="fas fa-calendar"></i>
                                                Avis laissé le <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
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

                                        <div class="review-actions" style="display: flex; flex-direction: column; justify-content: center; padding: 0 15px;">
                                            <a href="../reviews/review_details.php?id=<?php echo $review['id']; ?>" class="btn-action btn-secondary">
                                                <i class="fas fa-eye"></i> Voir
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
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> MN Home DZ. Tous droits réservés.</p>
        </div>
    </footer>
</body>

</html>
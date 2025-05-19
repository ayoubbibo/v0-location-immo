<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../booking/booking_functions.php';

// Check if user is logged in
$logged_in = isLoggedIn();

// Redirect to login if not logged in
if (!$logged_in) {
    header('Location: ../logins/connexion.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get database connection
$conn = getDbConnection();

// Get user data
$user_data = getUserData($conn, $_SESSION['user_id']);

// Get user's bookings
$bookings = getUserBookings($conn, $_SESSION['user_id']);

// Get user's properties bookings if they are a host
$property_bookings = [];
if ($user_data['user_type'] == 'host' || $user_data['user_type'] == 'admin') {
    $property_bookings = getOwnerBookings($conn, $_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations - MN Home DZ</title>
    <link rel="stylesheet" href="./bookings_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../images/Logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="nav-barre">
        <div class="logo-container">
            <a href="../index.php">
                <img class="Logo" src="../images/LogoBlack.png" alt="Logo" />
            </a>
        </div>

        <div class="auth-buttons">
            <?php
            if ($logged_in): // Check if user is logged in
                $profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : '../images/default-profile.jpg';
            ?>
                <div class="user-info">
                    <a href="profile_dashboard.php" style="text-decoration: none;">
                        <button class="button-profile">
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Picture" class="profile-pic" />
                            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                        </button>
                    </a>
                </div>
            <?php else: ?>
                <a href="../logins/connexion.php"><button class="button1">Connexion</button></a>
                <a href="../logins/formulaire.php"><button class="button2">Créer un compte</button></a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Bookings Container -->
    <div class="bookings-container">
        <div class="bookings-header">
            <h1>Mes réservations</h1>
            <p>Gérez vos réservations et voyages</p>
        </div>

        <div class="bookings-tabs">
            <div class="tab active" data-tab="my-bookings">Mes voyages</div>
            <?php if ($user_data['user_type'] == 'host' || $user_data['user_type'] == 'admin'): ?>
                <div class="tab" data-tab="property-bookings">Réservations reçues</div>
            <?php endif; ?>
        </div>

        <!-- My Bookings Tab -->
        <div class="tab-content active" id="my-bookings">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-suitcase"></i>
                    <h3>Vous n'avez pas encore de réservations</h3>
                    <p>Explorez nos propriétés et planifiez votre prochain voyage!</p>
                    <a href="../index.php#Propriétés" class="booking-action primary-action">Découvrir des logements</a>
                </div>
            <?php else: ?>
                <div class="booking-list">
                    <?php foreach ($bookings as $booking):
                        $photos = explode(',', $booking['photos']);
                        $photo = !empty($photos[0]) ? $photos[0] : 'images/default.jpg';
                    ?>
                        <div class="booking-card">
                            <div class="booking-image">
                                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($booking['title']) ?>">
                            </div>

                            <div class="booking-content">
                                <h3 class="booking-title"><?= htmlspecialchars($booking['title']) ?></h3>
                                <p class="booking-address">📍 <?= htmlspecialchars($booking['address']) ?></p>

                                <div class="booking-status status-<?= $booking['status'] ?>">
                                    <?php
                                    switch ($booking['status']) {
                                        case 'pending':
                                            echo '<i class="fas fa-clock"></i> En attente';
                                            break;
                                        case 'confirmed':
                                            echo '<i class="fas fa-check-circle"></i> Confirmée';
                                            break;
                                        case 'cancelled':
                                            echo '<i class="fas fa-times-circle"></i> Annulée';
                                            break;
                                        case 'completed':
                                            echo '<i class="fas fa-flag-checkered"></i> Terminée';
                                            break;
                                    }
                                    ?>
                                </div>

                                <div class="booking-details">
                                    <div class="booking-detail">
                                        <span class="detail-label">Arrivée</span>
                                        <span class="detail-value"><?= date('d/m/Y', strtotime($booking['check_in'])) ?></span>
                                    </div>

                                    <div class="booking-detail">
                                        <span class="detail-label">Départ</span>
                                        <span class="detail-value"><?= date('d/m/Y', strtotime($booking['check_out'])) ?></span>
                                    </div>

                                    <div class="booking-detail">
                                        <span class="detail-label">Voyageurs</span>
                                        <span class="detail-value"><?= $booking['guests'] ?></span>
                                    </div>

                                    <div class="booking-detail">
                                        <span class="detail-label">Nuits</span>
                                        <span class="detail-value"><?= $booking['nights'] ?></span>
                                    </div>
                                </div>

                                <div class="booking-price">
                                    Total: <?= number_format($booking['total_price'], 0, ',', ' ') ?> DA
                                </div>

                                <div class="booking-actions">
                                    <a href="../property/property_details.php?id=<?= $booking['property_id'] ?>" class="booking-action secondary-action">
                                        Voir le logement
                                    </a>

                                    <a href="../booking/booking_confirmation.php?id=<?= $booking['id'] ?>" class="booking-action primary-action">
                                        Détails
                                    </a>

                                    <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                        <button class="booking-action danger-action cancel-booking" data-booking-id="<?= $booking['id'] ?>">
                                            Annuler
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Property Bookings Tab -->
        <?php if ($user_data['user_type'] == 'host' || $user_data['user_type'] == 'admin'): ?>
            <div class="tab-content" id="property-bookings">
                <?php if (empty($property_bookings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-home"></i>
                        <h3>Vous n'avez pas encore reçu de réservations</h3>
                        <p>Les réservations pour vos propriétés apparaîtront ici.</p>
                    </div>
                <?php else: ?>
                    <div class="booking-list">
                        <?php foreach ($property_bookings as $booking):
                            $photos = explode(',', $booking['photos']);

                            if (strpos($photos[0], 'http') === 0) {
                                $photo = $photos[0];
                            } else {
                                $photo = !empty($photos[0]) ? '../annonces/' . $photos[0] : '../images/default.jpg';
                            }

                        ?>
                            <div class="booking-card">
                                <div class="booking-image">
                                    <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($booking['title']) ?>">
                                </div>

                                <div class="booking-content">
                                    <h3 class="booking-title"><?= htmlspecialchars($booking['title']) ?></h3>
                                    <p class="booking-address">📍 <?= htmlspecialchars($booking['address']) ?></p>

                                    <div class="booking-status status-<?= $booking['status'] ?>">
                                        <?php
                                        switch ($booking['status']) {
                                            case 'pending':
                                                echo '<i class="fas fa-clock"></i> En attente';
                                                break;
                                            case 'confirmed':
                                                echo '<i class="fas fa-check-circle"></i> Confirmée';
                                                break;
                                            case 'cancelled':
                                                echo '<i class="fas fa-times-circle"></i> Annulée';
                                                break;
                                            case 'completed':
                                                echo '<i class="fas fa-flag-checkered"></i> Terminée';
                                                break;
                                        }
                                        ?>
                                    </div>

                                    <div class="booking-details">
                                        <div class="booking-detail">
                                            <span class="detail-label">Voyageur</span>
                                            <span class="detail-value"><?= htmlspecialchars($booking['username']) ?></span>
                                        </div>

                                        <div class="booking-detail">
                                            <span class="detail-label">Contact</span>
                                            <span class="detail-value"><?= htmlspecialchars($booking['email']) ?></span>
                                        </div>

                                        <div class="booking-detail">
                                            <span class="detail-label">Arrivée</span>
                                            <span class="detail-value"><?= date('d/m/Y', strtotime($booking['check_in'])) ?></span>
                                        </div>

                                        <div class="booking-detail">
                                            <span class="detail-label">Départ</span>
                                            <span class="detail-value"><?= date('d/m/Y', strtotime($booking['check_out'])) ?></span>
                                        </div>

                                        <div class="booking-detail">
                                            <span class="detail-label">Voyageurs</span>
                                            <span class="detail-value"><?= $booking['guests'] ?></span>
                                        </div>

                                        <div class="booking-detail">
                                            <span class="detail-label">Nuits</span>
                                            <span class="detail-value"><?= $booking['nights'] ?></span>
                                        </div>
                                    </div>

                                    <div class="booking-price">
                                        Total: <?= number_format($booking['total_price'], 0, ',', ' ') ?> DA
                                    </div>

                                    <div class="booking-actions">
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <button class="booking-action primary-action confirm-booking" data-booking-id="<?= $booking['id'] ?>">
                                                Confirmer
                                            </button>

                                            <button class="booking-action danger-action cancel-booking" data-booking-id="<?= $booking['id'] ?>">
                                                Refuser
                                            </button>
                                        <?php else: ?>
                                            <a href="../property/property_details.php?id=<?= $booking['property_id'] ?>" class="booking-action secondary-action">
                                                Voir le logement
                                            </a>

                                            <?php if ($booking['status'] == 'confirmed'): ?>
                                                <button class="booking-action danger-action cancel-booking" data-booking-id="<?= $booking['id'] ?>">
                                                    Annuler
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });

            // Handle booking cancellation
            const cancelButtons = document.querySelectorAll('.cancel-booking');
            cancelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
                        const bookingId = this.getAttribute('data-booking-id');

                        fetch('../ajax/update_booking_status.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'booking_id=' + bookingId + '&status=cancelled'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('La réservation a été annulée avec succès.');
                                    window.location.reload();
                                } else {
                                    alert('Erreur: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Une erreur est survenue lors de l\'annulation de la réservation.');
                            });
                    }
                });
            });

            // Handle booking confirmation
            const confirmButtons = document.querySelectorAll('.confirm-booking');
            confirmButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')) {
                        const bookingId = this.getAttribute('data-booking-id');

                        fetch('../ajax/update_booking_status.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'booking_id=' + bookingId + '&status=confirmed'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('La réservation a été confirmée avec succès.');
                                    window.location.reload();
                                } else {
                                    alert('Erreur: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Une erreur est survenue lors de la confirmation de la réservation.');
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>
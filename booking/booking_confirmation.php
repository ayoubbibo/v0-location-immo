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

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get booking details
$booking = getBookingById($conn, $booking_id);

// If booking not found or user is not authorized to view it
if (!$booking || $booking['user_id'] != $_SESSION['user_id']) {
    header('Location: ../index.php');
    exit;
}

// Get property photos
$photos = explode(',', $booking['photos']);
$photo = !empty($photos[0]) ? '../annonces/' . $photos[0] : '../images/default.jpg';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de réservation - MN Home DZ</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../images/Logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .confirmation-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #4a6ee0;
        }
        
        .confirmation-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .confirmation-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #e65100;
        }
        
        .status-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .property-image {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .booking-info h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .booking-info-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-item .label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .info-item .value {
            font-size: 1.1rem;
        }
        
        .booking-summary {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .booking-summary h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }
        
        .price-details {
            margin-bottom: 1.5rem;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 1.2rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }
        
        .booking-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        
        .action-button {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        
        .primary-button {
            background: #4a6ee0;
            color: white;
            border: none;
        }
        
        .primary-button:hover {
            background: #3a5ecc;
        }
        
        .secondary-button {
            background: white;
            color: #4a6ee0;
            border: 1px solid #4a6ee0;
        }
        
        .secondary-button:hover {
            background: #f5f7ff;
        }
        
        .danger-button {
            background: white;
            color: #c62828;
            border: 1px solid #c62828;
        }
        
        .danger-button:hover {
            background: #ffebee;
        }
        
        @media (max-width: 768px) {
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .booking-info-list {
                grid-template-columns: 1fr;
            }
            
            .booking-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .action-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="nav-barre">
        <div class="logo-container">
            <a href="../index.php">
                <img class="Logo" src="../images/LogoBlack.png" alt="Logo" />
            </a>
        </div>

        <div class="nav-links">
            <ul>
                <li><a href="../index.php#Accueil">Accueil</a></li>
                <li><a href="../index.php#Rechercher">Rechercher</a></li>
                <li><a href="../index.php#Propriétés">Propriétés</a></li>
            </ul>
        </div>

        <div class="auth-buttons">
            <?php
            if ($logged_in): // Check if user is logged in
                $user_data = getUserData($conn, $_SESSION['user_id']);
                $profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : '../images/default-profile.jpg';
            ?>
                <div class="user-info">
                    <a href="../profile/profile_dashboard.php">
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

    <!-- Booking Confirmation -->
    <div class="confirmation-container">
        <div class="confirmation-header">
            <h1>Confirmation de réservation</h1>
            <p>Merci pour votre réservation chez MN Home DZ</p>
        </div>
        
        <div class="confirmation-status status-<?= $booking['status'] ?>">
            <?php if ($booking['status'] == 'pending'): ?>
                <i class="fas fa-clock"></i>
                <span>Votre réservation est en attente de confirmation par le propriétaire</span>
            <?php elseif ($booking['status'] == 'confirmed'): ?>
                <i class="fas fa-check-circle"></i>
                <span>Votre réservation est confirmée</span>
            <?php elseif ($booking['status'] == 'cancelled'): ?>
                <i class="fas fa-times-circle"></i>
                <span>Votre réservation a été annulée</span>
            <?php endif; ?>
        </div>
        
        <div class="booking-details">
            <div class="property-image">
                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($booking['title']) ?>">
            </div>
            
            <div class="booking-info">
                <h2><?= htmlspecialchars($booking['title']) ?></h2>
                <p class="property-address">📍 <?= htmlspecialchars($booking['address']) ?></p>
                
                <div class="booking-info-list">
                    <div class="info-item">
                        <div class="label">Arrivée</div>
                        <div class="value"><?= date('d/m/Y', strtotime($booking['check_in'])) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Départ</div>
                        <div class="value"><?= date('d/m/Y', strtotime($booking['check_out'])) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Voyageurs</div>
                        <div class="value"><?= $booking['guests'] ?> personne(s)</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Durée</div>
                        <div class="value"><?= $booking['nights'] ?> nuit(s)</div>
                    </div>
                </div>
                
                <div class="booking-summary">
                    <h3>Résumé du prix</h3>
                    
                    <div class="price-details">
                        <div class="price-row">
                            <div>Prix par nuit</div>
                            <div><?= number_format($booking['total_price'] / $booking['nights'], 0, ',', ' ') ?> DA</div>
                        </div>
                        
                        <div class="price-row">
                            <div><?= number_format($booking['total_price'] / $booking['nights'], 0, ',', ' ') ?> DA x <?= $booking['nights'] ?> nuits</div>
                            <div><?= number_format($booking['total_price'], 0, ',', ' ') ?> DA</div>
                        </div>
                        
                        <div class="total-row">
                            <div>Total</div>
                            <div><?= number_format($booking['total_price'], 0, ',', ' ') ?> DA</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="booking-actions">
            <a href="../property/property_details.php?id=<?= $booking['property_id'] ?>" class="action-button secondary-button">
                Voir le logement
            </a>
            
            <a href="../profile/bookings.php" class="action-button primary-button">
                Voir mes réservations
            </a>
            
            <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                <button id="cancel-booking" class="action-button danger-button" data-booking-id="<?= $booking['id'] ?>">
                    Annuler la réservation
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle booking cancellation
            const cancelButton = document.getElementById('cancel-booking');
            if (cancelButton) {
                cancelButton.addEventListener('click', function() {
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
                                alert('Votre réservation a été annulée avec succès.');
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
            }
        });
    </script>
</body>
</html>

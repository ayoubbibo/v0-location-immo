<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../booking/booking_functions.php';

// Require login
requireLogin();

// Get database connection
$conn = getDbConnection();

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$booking_id) {
    header("Location: ../profile/bookings.php");
    exit;
}

// Get booking details
$booking = getBookingDetails($conn, $booking_id, $_SESSION['user_id']);

// If booking not found or user not authorized
if (!$booking) {
    header("Location: ../profile/bookings.php");
    exit;
}

// Determine if user is guest or host
$is_guest = ($booking['user_id'] == $_SESSION['user_id']);
$is_host = ($booking['host_id'] == $_SESSION['user_id']);

// Process status update
$status_message = '';
$status_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $result = updateBookingStatus($conn, $booking_id, $new_status, $_SESSION['user_id']);
    
    $status_success = $result['success'];
    $status_message = $result['message'];
    
    if ($status_success) {
        // Refresh booking data
        $booking = getBookingDetails($conn, $booking_id, $_SESSION['user_id']);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de réservation - MN Home DZ</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../images/Logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background-color: transparent;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        
        .back-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ff385c;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: #ff385c;
        }
        
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }
        
        .booking-details {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .booking-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .booking-id {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .booking-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
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
        
        .status-completed {
            background: #e0f2f1;
            color: #00695c;
        }
        
        .booking-dates {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .date-item {
            flex: 1;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .date-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .date-value {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .booking-content {
            padding: 1.5rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .property-info {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .property-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .property-details {
            flex: 1;
        }
        
        .property-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .property-address {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .property-features {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #555;
        }
        
        .contact-info {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .contact-item {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .contact-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .contact-value {
            font-weight: 600;
        }
        
        .booking-summary {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .summary-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .summary-label {
            color: #666;
        }
        
        .summary-value {
            font-weight: 600;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .booking-actions {
            width: 90%;
            margin-top: 1.5rem;
        }
        
        .action-button {
            display: block;
            width: 100%;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 1rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #ff385c;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #ff385c;
        }
        
        .btn-secondary {
            background: white;
            color: #ff385c;
            border: 1px solid #ff385c;
        }
        
        .btn-secondary:hover {
            background: #f5f7ff;
        }
        
        .btn-danger {
            background: white;
            color: #e53935;
            border: 1px solid #e53935;
        }
        
        .btn-danger:hover {
            background: #ffebee;
        }
        
        .status-form {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        @media (max-width: 768px) {
            .booking-container {
                grid-template-columns: 1fr;
            }
            
            .property-info {
                flex-direction: column;
            }
            
            .property-image {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>

<body>
    <nav class="nav-barre">
        <div class="logo-container">
            <a href="../index.php">
                <img class="Logo" src="../images/LogoBlack.png" alt="Logo" />
            </a>
        </div>

        <div>
            <?php if (isLoggedIn()): ?>
                <a href="../profile/profile_dashboard.php"><button class="button1">Mon Compte</button></a>
                <a href="../logins/logout.php"><button class="button2">Déconnexion</button></a>
            <?php else: ?>
                <a href="../logins/connexion.php"><button class="button1">Connexion</button></a>
                <a href="../logins/formulaire.php"><button class="button2">Créer un compte</button></a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Détails de la réservation</h1>
            <a href="../profile/bookings.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour aux réservations
            </a>
        </div>

        <?php if ($status_message): ?>
            <div class="alert <?php echo $status_success ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>

        <div class="booking-container">
            <div class="booking-details">
                <div class="booking-header">
                    <div class="booking-id">Réservation #<?php echo $booking_id; ?></div>
                    
                    <div class="booking-status status-<?php echo $booking['status']; ?>">
                        <?php
                        switch ($booking['status']) {
                            case 'pending':
                                echo '<i class="fas fa-clock"></i> En attente de confirmation';
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
                    
                    <div class="booking-dates">
                        <div class="date-item">
                            <div class="date-label">Date d'arrivée</div>
                            <div class="date-value"><?php echo date('d/m/Y', strtotime($booking['check_in'])); ?></div>
                        </div>
                        
                        <div class="date-item">
                            <div class="date-label">Date de départ</div>
                            <div class="date-value"><?php echo date('d/m/Y', strtotime($booking['check_out'])); ?></div>
                        </div>
                        
                        <div class="date-item">
                            <div class="date-label">Durée</div>
                            <div class="date-value"><?php echo $booking['nights']; ?> nuit(s)</div>
                        </div>
                    </div>
                </div>
                
                <div class="booking-content">
                    <h2 class="section-title">Détails du logement</h2>
                    
                    <div class="property-info">
                        <div class="property-image">
                            <img src="<?php echo htmlspecialchars($booking['main_photo']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>">
                        </div>
                        
                        <div class="property-details">
                            <h3 class="property-title"><?php echo htmlspecialchars($booking['title']); ?></h3>
                            
                            <div class="property-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($booking['address']); ?></span>
                            </div>
                            
                            <div class="property-features">
                                <div class="feature-item">
                                    <i class="fas fa-home"></i>
                                    <span><?php echo ucfirst(htmlspecialchars($booking['housing_type'])); ?></span>
                                </div>
                                
                                <div class="feature-item">
                                    <i class="fas fa-expand-arrows-alt"></i>
                                    <span><?php echo htmlspecialchars($booking['area']); ?> m²</span>
                                </div>
                                
                                <div class="feature-item">
                                    <i class="fas fa-bed"></i>
                                    <span><?php echo htmlspecialchars($booking['number_of_rooms']); ?> pièces</span>
                                </div>
                                
                                <div class="feature-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo htmlspecialchars($booking['guests']); ?> voyageurs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h2 class="section-title">Informations de contact</h2>
                    
                    <div class="contact-info">
                        <div class="contact-grid">
                            <?php if ($is_guest): ?>
                                <div class="contact-item">
                                    <div class="contact-label">Hôte</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($booking['host_name']); ?></div>
                                </div>
                                
                                <div class="contact-item">
                                    <div class="contact-label">Email de l'hôte</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($booking['host_email']); ?></div>
                                </div>
                                
                                <div class="contact-item">
                                    <div class="contact-label">Téléphone de l'hôte</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($booking['host_phone']); ?></div>
                                </div>
                            <?php else: ?>
                                <div class="contact-item">
                                    <div class="contact-label">Voyageur</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                                </div>
                                
                                <div class="contact-item">
                                    <div class="contact-label">Email du voyageur</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($booking['guest_email']); ?></div>
                                </div>
                                
                                <div class="contact-item">
                                    <div class="contact-label">Téléphone du voyageur</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($booking['guest_phone']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($is_host && $booking['status'] === 'pending'): ?>
                        <div class="status-form">
                            <h2 class="section-title">Gérer cette réservation</h2>
                            
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="status" class="form-label">Changer le statut</label>
                                    <select id="status" name="status" class="form-select">
                                        <option value="confirmed">Confirmer la réservation</option>
                                        <option value="cancelled">Annuler la réservation</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="update_status" class="action-button btn-primary">
                                    Mettre à jour le statut
                                </button>
                            </form>
                        </div>
                    <?php elseif ($is_guest && ($booking['status'] === 'pending' || $booking['status'] === 'confirmed')): ?>
                        <div class="status-form">
                            <h2 class="section-title">Gérer cette réservation</h2>
                            
                            <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" name="update_status" class="action-button btn-danger">
                                    Annuler cette réservation
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="booking-summary">
                <h2 class="summary-title">Résumé de la réservation</h2>
                
                <div class="summary-item">
                    <div class="summary-label">Prix par nuit</div>
                    <div class="summary-value"><?php echo number_format($booking['price'], 0, ',', ' '); ?> DA</div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-label"><?php echo number_format($booking['price'], 0, ',', ' '); ?> DA x <?php echo $booking['nights']; ?> nuits</div>
                    <div class="summary-value"><?php echo number_format($booking['price'] * $booking['nights'], 0, ',', ' '); ?> DA</div>
                </div>
                
                <div class="summary-total">
                    <div>Total</div>
                    <div><?php echo number_format($booking['total_price'], 0, ',', ' '); ?> DA</div>
                </div>
                
                <div class="booking-actions">
                    <a href="../property/property_details.php?id=<?php echo $booking['property_id']; ?>" class="action-button btn-secondary">
                        Voir le logement
                    </a>
                    
                    <?php if ($booking['status'] === 'confirmed' && $is_guest): ?>
                        <a href="#" class="action-button btn-primary">
                            Contacter l'hôte
                        </a>
                    <?php elseif ($booking['status'] === 'confirmed' && $is_host): ?>
                        <a href="#" class="action-button btn-primary">
                            Contacter le voyageur
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

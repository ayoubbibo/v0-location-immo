<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once 'booking_functions.php';

// Require login
requireLogin();

$error_message = '';
$success_message = '';

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$booking_id) {
    header("Location: ../profile/dashboard.php");
    exit;
}

// Get booking details
$booking = getBookingDetails($conn, $booking_id, $_SESSION['user_id']);

if (!$booking) {
    header("Location: ../profile/dashboard.php");
    exit;
}

// Process booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'cancel') {
        $result = updateBookingStatus($conn, $booking_id, 'cancelled', $_SESSION['user_id']);
        
        if ($result['success']) {
            $success_message = 'La réservation a été annulée avec succès.';
            // Refresh booking details
            $booking = getBookingDetails($conn, $booking_id, $_SESSION['user_id']);
        } else {
            $error_message = $result['message'];
        }
    } elseif ($action === 'confirm' && $_SESSION['user_id'] === $booking['host_id']) {
        $result = updateBookingStatus($conn, $booking_id, 'confirmed', $_SESSION['user_id']);
        
        if ($result['success']) {
            $success_message = 'La réservation a été confirmée avec succès.';
            // Refresh booking details
            $booking = getBookingDetails($conn, $booking_id, $_SESSION['user_id']);
        } else {
            $error_message = $result['message'];
        }
    }
}

// Determine if user is guest or host
$is_guest = $_SESSION['user_id'] === $booking['user_id'];
$is_host = $_SESSION['user_id'] === $booking['host_id'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la réservation - MN Home DZ</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .booking-container {
            max-width: 1000px;
            margin: 120px auto 50px;
            padding: 0 20px;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .booking-status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .status-completed {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .booking-details, .property-details, .guest-details, .host-details {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
            color: #354464;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            width: 40%;
            font-weight: 600;
            color: #4b5563;
        }
        
        .detail-value {
            width: 60%;
        }
        
        .property-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .property-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .property-location {
            color: #6b7280;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .price-details {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .price-total {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-weight: bold;
            font-size: 18px;
        }
        
        .booking-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-action {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-confirm {
            background-color: #5D76A9;
            color: white;
        }
        
        .btn-confirm:hover {
            background-color: #4a5d8a;
        }
        
        .btn-cancel {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        
        .btn-cancel:hover {
            background-color: #e5e7eb;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        @media (max-width: 768px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="nav-barre">
        <div>
            <a href="../index.php">
                <img class="Logo" src="../images/Logo.png" alt="Logo" />
            </a>
        </div>
        
        <div class="div-de-ul">
            <ul>
                <li><a href="../index.php#Accueil">Accueil</a></li>
                <li><a href="../index.php#Rechercher">Rechercher</a></li>
                <li><a href="../index.php#Propriétés">Propriétés</a></li>
            </ul>
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
    
    <div class="booking-container">
        <div class="booking-header">
            <h1>Détails de la réservation</h1>
            
            <?php
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
            
            <span class="booking-status <?php echo $status_class; ?>">
                <?php echo $status_text; ?>
            </span>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="booking-grid">
            <div class="booking-details">
                <h3 class="section-title">Informations de réservation</h3>
                
                <div class="detail-row">
                    <div class="detail-label">Numéro de réservation</div>
                    <div class="detail-value">#<?php echo $booking['id']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date d'arrivée</div>
                    <div class="detail-value"><?php echo date('d/m/Y', strtotime($booking['check_in'])); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date de départ</div>
                    <div class="detail-value"><?php echo date('d/m/Y', strtotime($booking['check_out'])); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Nombre de nuits</div>
                    <div class="detail-value"><?php echo $booking['nights']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Nombre de personnes</div>
                    <div class="detail-value"><?php echo $booking['guests']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date de réservation</div>
                    <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></div>
                </div>
                
                <div class="price-details">
                    <div class="price-row">
                        <span><?php echo number_format($booking['tarif']); ?> DA x <?php echo $booking['nights']; ?> nuits</span>
                        <span><?php echo number_format($booking['total_price']); ?> DA</span>
                    </div>
                    
                    <div class="price-total">
                        <span>Total</span>
                        <span><?php echo number_format($booking['total_price']); ?> DA</span>
                    </div>
                </div>
            </div>
            
            <div class="property-details">
                <h3 class="section-title">Détails de la propriété</h3>
                
                <img src="<?php echo htmlspecialchars($booking['main_photo']); ?>" alt="<?php echo htmlspecialchars($booking['titre']); ?>" class="property-image">
                
                <h4 class="property-title"><?php echo htmlspecialchars($booking['titre']); ?></h4>
                
                <p class="property-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($booking['adresse']); ?>
                </p>
                
                <div class="detail-row">
                    <div class="detail-label">Type de logement</div>
                    <div class="detail-value"><?php echo ucfirst(htmlspecialchars($booking['type_logement'] ?? 'Non spécifié')); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Superficie</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['supperficie'] ?? 'Non spécifié'); ?> m²</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Nombre de pièces</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['nombre_pieces'] ?? 'Non spécifié'); ?></div>
                </div>
            </div>
            
            <?php if ($is_guest): ?>
            <div class="host-details">
                <h3 class="section-title">Informations sur l'hôte</h3>
                
                <div class="detail-row">
                    <div class="detail-label">Nom</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['host_name']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['host_email']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Téléphone</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['host_phone']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($is_host): ?>
            <div class="guest-details">
                <h3 class="section-title">Informations sur le voyageur</h3>
                
                <div class="detail-row">
                    <div class="detail-label">Nom</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['guest_email']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Téléphone</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['guest_phone']); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($booking['status'] === 'pending'): ?>
            <div class="booking-actions">
                <?php if ($is_host): ?>
                    <form action="booking-details.php?id=<?php echo $booking_id; ?>" method="POST">
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" class="btn-action btn-confirm">Confirmer la réservation</button>
                    </form>
                <?php endif; ?>
                
                <form action="booking-details.php?id=<?php echo $booking_id; ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?');">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn-action btn-cancel">Annuler la réservation</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

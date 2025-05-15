<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once 'booking_functions.php';

// Require login
requireLogin();

$error_message = '';
$success_message = '';

// Get property ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$property_id) {
    header("Location: ../index.php");
    exit;
}

// Get property details
$property_sql = "SELECT * FROM annonce WHERE id = ? AND valide = 1";
$property_stmt = $conn->prepare($property_sql);
$property_stmt->bind_param("i", $property_id);
$property_stmt->execute();
$property_result = $property_stmt->get_result();

if ($property_result->num_rows === 0) {
    header("Location: ../index.php");
    exit;
}

$property = $property_result->fetch_assoc();

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $guests = isset($_POST['guests']) ? intval($_POST['guests']) : 0;
    
    // Validate inputs
    if (empty($check_in) || empty($check_out) || $guests <= 0) {
        $error_message = 'Veuillez remplir tous les champs.';
    } elseif (strtotime($check_in) < strtotime('today')) {
        $error_message = 'La date d\'arrivée doit être aujourd\'hui ou après.';
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error_message = 'La date de départ doit être après la date d\'arrivée.';
    } elseif ($guests > $property['nombre_personnes']) {
        $error_message = 'Le nombre de personnes dépasse la capacité maximale.';
    } else {
        // Calculate total price
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
        $nights = $check_out_date->diff($check_in_date)->days;
        $total_price = $nights * $property['tarif'];
        
        // Create booking
        $result = createBooking($conn, $property_id, $_SESSION['user_id'], $check_in, $check_out, $guests, $total_price);
        
        if ($result['success']) {
            $success_message = 'Votre demande de réservation a été envoyée avec succès!';
            // Redirect to booking details after 2 seconds
            header("refresh:2;url=booking-details.php?id=" . $result['booking_id']);
        } else {
            $error_message = $result['message'];
        }
    }
}

// Get property photos
$photos = explode(',', $property['photos']);
$main_photo = !empty($photos[0]) ? '../annonces/' . $photos[0] : '../images/default.jpg';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver - <?php echo htmlspecialchars($property['titre']); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .booking-container {
            max-width: 1200px;
            margin: 120px auto 50px;
            padding: 0 20px;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .property-details {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .property-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .property-info {
            padding: 20px;
        }
        
        .property-title {
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .property-location {
            color: #6b7280;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .property-features {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #4b5563;
        }
        
        .booking-form {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
            color: #354464;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4b5563;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: #5D76A9;
            outline: none;
        }
        
        .price-details {
            margin-top: 30px;
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
        
        .btn-book {
            width: 100%;
            background-color: #5D76A9;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        
        .btn-book:hover {
            background-color: #4a5d8a;
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
        <h1>Réserver</h1>
        
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
            <div class="property-details">
                <img src="<?php echo htmlspecialchars($main_photo); ?>" alt="<?php echo htmlspecialchars($property['titre']); ?>" class="property-image">
                
                <div class="property-info">
                    <h2 class="property-title"><?php echo htmlspecialchars($property['titre']); ?></h2>
                    
                    <p class="property-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($property['adresse']); ?>
                    </p>
                    
                    <div class="property-features">
                        <div class="feature">
                            <i class="fas fa-home"></i>
                            <span><?php echo htmlspecialchars($property['type_logement']); ?></span>
                        </div>
                        
                        <div class="feature">
                            <i class="fas fa-vector-square"></i>
                            <span><?php echo htmlspecialchars($property['supperficie']); ?> m²</span>
                        </div>
                        
                        <div class="feature">
                            <i class="fas fa-bed"></i>
                            <span><?php echo htmlspecialchars($property['nombre_pieces']); ?> pièces</span>
                        </div>
                        
                        <div class="feature">
                            <i class="fas fa-user-friends"></i>
                            <span>Max <?php echo htmlspecialchars($property['nombre_personnes']); ?> personnes</span>
                        </div>
                    </div>
                    
                    <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                </div>
            </div>
            
            <div class="booking-form">
                <h3 class="form-title">Détails de la réservation</h3>
                
                <form action="book.php?id=<?php echo $property_id; ?>" method="POST" id="booking-form">
                    <div class="form-group">
                        <label for="check_in">Date d'arrivée</label>
                        <input type="date" id="check_in" name="check_in" class="form-control" required 
                               min="<?php echo date('Y-m-d'); ?>" 
                               max="<?php echo $property['date_fin']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="check_out">Date de départ</label>
                        <input type="date" id="check_out" name="check_out" class="form-control" required 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                               max="<?php echo $property['date_fin']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="guests">Nombre de personnes</label>
                        <input type="number" id="guests" name="guests" class="form-control" required 
                               min="1" max="<?php echo $property['nombre_personnes']; ?>" value="1">
                    </div>
                    
                    <div class="price-details">
                        <div class="price-row">
                            <span><?php echo htmlspecialchars($property['tarif']); ?> DA x <span id="nights">0</span> nuits</span>
                            <span id="subtotal">0 DA</span>
                        </div>
                        
                        <div class="price-total">
                            <span>Total</span>
                            <span id="total">0 DA</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-book">Réserver maintenant</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Calculate price based on dates
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        const nightsElement = document.getElementById('nights');
        const subtotalElement = document.getElementById('subtotal');
        const totalElement = document.getElementById('total');
        const pricePerNight = <?php echo $property['tarif']; ?>;
        
        function calculatePrice() {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            
            if (checkIn && checkOut && checkOut > checkIn) {
                const diffTime = Math.abs(checkOut - checkIn);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                const subtotal = diffDays * pricePerNight;
                
                nightsElement.textContent = diffDays;
                subtotalElement.textContent = subtotal.toLocaleString() + ' DA';
                totalElement.textContent = subtotal.toLocaleString() + ' DA';
            } else {
                nightsElement.textContent = '0';
                subtotalElement.textContent = '0 DA';
                totalElement.textContent = '0 DA';
            }
        }
        
        checkInInput.addEventListener('change', function() {
            // Update check-out min date
            const checkInDate = new Date(this.value);
            const nextDay = new Date(checkInDate);
            nextDay.setDate(nextDay.getDate() + 1);
            
            const year = nextDay.getFullYear();
            const month = String(nextDay.getMonth() + 1).padStart(2, '0');
            const day = String(nextDay.getDate()).padStart(2, '0');
            
            checkOutInput.min = `${year}-${month}-${day}`;
            
            // If check-out date is before new check-in date, reset it
            if (new Date(checkOutInput.value) <= checkInDate) {
                checkOutInput.value = `${year}-${month}-${day}`;
            }
            
            calculatePrice();
        });
        
        checkOutInput.addEventListener('change', calculatePrice);
    </script>
</body>
</html>

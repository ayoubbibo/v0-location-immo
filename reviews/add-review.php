<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once 'review_functions.php';

// Require login
requireLogin();

$error_message = '';
$success_message = '';

// Get property ID and booking ID from URL
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$property_id) {
    header("Location: ../profile/dashboard.php");
    exit;
}

// Get property details
$property_sql = "SELECT * FROM annonce WHERE id = ? AND valide = 1";
$property_stmt = $conn->prepare($property_sql);
$property_stmt->bind_param("i", $property_id);
$property_stmt->execute();
$property_result = $property_stmt->get_result();

if ($property_result->num_rows === 0) {
    header("Location: ../profile/dashboard.php");
    exit;
}

$property = $property_result->fetch_assoc();

// Check if user has already reviewed this property
$check_sql = "SELECT id FROM reviews WHERE property_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $property_id, $_SESSION['user_id']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $error_message = 'Vous avez déjà laissé un avis pour cette propriété.';
}

// Process review form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = $_POST['comment'] ?? '';
    
    // Validate inputs
    if ($rating < 1 || $rating > 5) {
        $error_message = 'Veuillez sélectionner une note entre 1 et 5.';
    } elseif (empty($comment)) {
        $error_message = 'Veuillez laisser un commentaire.';
    } else {
        // Add review
        $result = addReview($conn, $property_id, $_SESSION['user_id'], $booking_id, $rating, $comment);
        
        if ($result['success']) {
            $success_message = 'Votre avis a été ajouté avec succès!';
            // Redirect to property page after 2 seconds
            header("refresh:2;url=../detail_bien.php?id=" . $property_id);
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
    <title>Ajouter un avis - <?php echo htmlspecialchars($property['titre']); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .review-container {
            max-width: 800px;
            margin: 120px auto 50px;
            padding: 0 20px;
        }
        
        .review-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            height: 200px;
            object-fit: cover;
        }
        
        .property-info {
            padding: 20px;
        }
        
        .property-title {
            font-size: 20px;
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
        
        .review-form {
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
        
        .rating-container {
            margin-bottom: 20px;
        }
        
        .rating-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #4b5563;
        }
        
        .rating-stars {
            display: flex;
            gap: 10px;
        }
        
        .rating-stars input {
            display: none;
        }
        
        .rating-stars label {
            cursor: pointer;
            font-size: 30px;
            color: #d1d5db;
        }
        
        .rating-stars input:checked ~ label {
            color: #f59e0b;
        }
        
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: #f59e0b;
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
            resize: vertical;
            min-height: 150px;
        }
        
        .form-control:focus {
            border-color: #5D76A9;
            outline: none;
        }
        
        .btn-submit {
            width: 100%;
            background-color: #5D76A9;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
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
            .review-grid {
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
    
    <div class="review-container">
        <h1>Ajouter un avis</h1>
        
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
        
        <div class="review-grid">
            <div class="property-details">
                <img src="<?php echo htmlspecialchars($main_photo); ?>" alt="<?php echo htmlspecialchars($property['titre']); ?>" class="property-image">
                
                <div class="property-info">
                    <h2 class="property-title"><?php echo htmlspecialchars($property['titre']); ?></h2>
                    
                    <p class="property-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($property['adresse']); ?>
                    </p>
                    
                    <p><?php echo nl2br(htmlspecialchars(substr($property['description'], 0, 200))); ?>...</p>
                </div>
            </div>
            
            <div class="review-form">
                <h3 class="form-title">Votre avis</h3>
                
                <form action="add-review.php?property_id=<?php echo $property_id; ?>&booking_id=<?php echo $booking_id; ?>" method="POST">
                    <div class="rating-container">
                        <label class="rating-label">Note</label>
                        
                        <div class="rating-stars">
                            <input type="radio" name="rating" id="star5" value="5" required>
                            <label for="star5" class="fas fa-star"></label>
                            
                            <input type="radio" name="rating" id="star4" value="4">
                            <label for="star4" class="fas fa-star"></label>
                            
                            <input type="radio" name="rating" id="star3" value="3">
                            <label for="star3" class="fas fa-star"></label>
                            
                            <input type="radio" name="rating" id="star2" value="2">
                            <label for="star2" class="fas fa-star"></label>
                            
                            <input type="radio" name="rating" id="star1" value="1">
                            <label for="star1" class="fas fa-star"></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Commentaire</label>
                        <textarea id="comment" name="comment" class="form-control" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">Soumettre l'avis</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Reverse the stars for the rating system
        document.addEventListener('DOMContentLoaded', function() {
            const ratingStars = document.querySelector('.rating-stars');
            const stars = Array.from(ratingStars.children);
            
            // Reverse the order of the stars
            for (let i = stars.length - 1; i >= 0; i--) {
                ratingStars.appendChild(stars[i]);
            }
        });
    </script>
</body>
</html>

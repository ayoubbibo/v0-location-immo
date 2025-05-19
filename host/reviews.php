<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../reviews/review_functions.php';

// Require login
requireLogin();

// Get database connection
$conn = getDbConnection();

// Check if user is a host or admin
$user = getUserData($conn, $_SESSION['user_id']);
if ($user['user_type'] != 'host' && $user['user_type'] != 'admin') {
    header("Location: ../profile/profile_dashboard.php");
    exit;
}

// Get host reviews
$reviews = getHostReviews($conn, $_SESSION['user_id']);

// Get average rating
$average_rating = getHostAverageRating($conn, $_SESSION['user_id']);

// Group reviews by property
$reviews_by_property = [];
foreach ($reviews as $review) {
    $property_id = $review['property_id'];
    if (!isset($reviews_by_property[$property_id])) {
        $reviews_by_property[$property_id] = [
            'property_id' => $property_id,
            'title' => $review['title'],
            'reviews' => []
        ];
    }
    $reviews_by_property[$property_id]['reviews'][] = $review;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes avis - MN Home DZ</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../images/Logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .rating-value {
            font-size: 3rem;
            font-weight: 700;
            color: #ff385c;
        }
        
        .rating-stars {
            display: flex;
            gap: 0.25rem;
            color: #ffc107;
            font-size: 1.5rem;
        }
        
        .rating-count {
            color: #666;
            font-size: 1.1rem;
        }
        
        .reviews-section {
            margin-bottom: 3rem;
        }
        
        .property-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .property-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .property-link {
            color: #4a6ee0;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .property-link:hover {
            color: #3a5ecc;
            text-decoration: underline;
        }
        
        .reviews-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .review-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .review-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .reviewer-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .reviewer-info {
            flex: 1;
        }
        
        .reviewer-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .review-date {
            font-size: 0.9rem;
            color: #666;
        }
        
        .review-rating {
            display: flex;
            gap: 0.25rem;
            color: #ffc107;
        }
        
        .review-content {
            padding: 1.5rem;
        }
        
        .review-text {
            color: #555;
            line-height: 1.6;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .reviews-list {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .rating-summary {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
            <h1 class="page-title">Mes avis</h1>
            <a href="host_dashboard.php" class="btn-action btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>

        <div class="rating-summary">
            <div class="rating-value"><?php echo number_format($average_rating, 1); ?></div>
            
            <div class="rating-stars">
                <?php
                $full_stars = floor($average_rating);
                $half_star = $average_rating - $full_stars >= 0.5;
                $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                
                for ($i = 0; $i < $full_stars; $i++) {
                    echo '<i class="fas fa-star"></i>';
                }
                
                if ($half_star) {
                    echo '<i class="fas fa-star-half-alt"></i>';
                }
                
                for ($i = 0; $i < $empty_stars; $i++) {
                    echo '<i class="far fa-star"></i>';
                }
                ?>
            </div>
            
            <div class="rating-count"><?php echo count($reviews); ?> avis au total</div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <h3>Aucun avis</h3>
                <p>Vous n'avez pas encore reçu d'avis pour vos propriétés.</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews_by_property as $property): ?>
                <div class="reviews-section">
                    <div class="property-header">
                        <h2 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h2>
                        <a href="../property/property_details.php?id=<?php echo $property['property_id']; ?>" class="property-link">
                            Voir la propriété <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    
                    <div class="reviews-list">
                        <?php foreach ($property['reviews'] as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <img src="<?php echo htmlspecialchars($review['profile_image']); ?>" alt="<?php echo htmlspecialchars($review['username']); ?>" class="reviewer-image">
                                    
                                    <div class="reviewer-info">
                                        <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                        <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
                                    </div>
                                    
                                    <div class="review-rating">
                                        <?php
                                        for ($i = 0; $i < 5; $i++) {
                                            if ($i < $review['rating']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="review-content">
                                    <p class="review-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>

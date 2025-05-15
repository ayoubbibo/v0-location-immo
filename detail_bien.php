<?php
require_once 'config.php';
require_once 'auth/auth_functions.php';
require_once 'property/property_functions.php';
require_once 'reviews/review_functions.php';

// Check if user is logged in
$logged_in = isLoggedIn();

// Get database connection
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get property ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch property details
$property = getPropertyById($conn, $property_id);
if (!$property) {
    header('Location: index.php');
    exit;
}

// Get all photos for the property
$photos = getAllPhotoUrls($property['photos']);

// Check if property is in user's favorites
$is_favorite = $logged_in ? isPropertyInFavorites($conn, $_SESSION['user_id'], $property_id) : false;

// Get owner information
$owner = getUserData($conn, $property['proprietaire_id']);

// Get reviews for the property
$reviews = getPropertyReviews($conn, $property_id);

// Calculate average rating
$avg_rating = 0;
$review_count = count($reviews);
if ($review_count > 0) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $avg_rating = round($total_rating / $review_count, 1);
}

// Get amenities
$amenities = !empty($property['equipements']) ? explode(',', $property['equipements']) : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['titre']) ?> - MN Home DZ</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="images/Logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="nav-barre">
        <div class="logo-container">
            <a href="index.php">
                <img class="Logo" src="images/Logo.png" alt="Logo" />
            </a>
        </div>

        <div class="nav-links">
            <ul>
                <li><a href="index.php#Accueil">Accueil</a></li>
                <li><a href="index.php#Rechercher">Rechercher</a></li>
                <li><a href="index.php#Propriétés">Propriétés</a></li>
            </ul>
        </div>

        <div class="auth-buttons">
            <?php if ($logged_in): ?>
                <div class="user-info">
                    <a href="profile/profile_dashboard.php">
                        <button class="button-profile">
                            <img src="<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile Picture" class="profile-pic" />
                            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                        </button>
                    </a>
                </div>
            <?php else: ?>
                <a href="logins/connexion.php"><button class="button1">Connexion</button></a>
                <a href="logins/formulaire.php"><button class="button2">Créer un compte</button></a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Property Container -->
    <div class="property-container">
        <!-- Property Header -->
        <div class="property-header">
            <h1 class="property-title"><?= htmlspecialchars($property['titre']) ?></h1>
            <div class="property-subtitle">
                <div class="property-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= htmlspecialchars($property['adresse']) ?></span>
                </div>
                <div class="property-actions">
                    <button class="action-button">
                        <i class="fas fa-share"></i>
                        <span>Partager</span>
                    </button>
                    <?php if ($logged_in): ?>
                        <button class="action-button favorite-toggle" data-property-id="<?= $property_id ?>">
                            <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-heart"></i>
                            <span>Enregistrer</span>
                        </button>
                    <?php else: ?>
                        <a href="logins/connexion.php" class="action-button">
                            <i class="far fa-heart"></i>
                            <span>Enregistrer</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Property Gallery -->
        <div class="property-gallery">
            <?php foreach ($photos as $index => $photo): ?>
                <div class="gallery-item <?= $index === 0 ? 'main' : '' ?>">
                    <img src="<?= htmlspecialchars($photo) ?>" alt="Photo <?= $index + 1 ?> de <?= htmlspecialchars($property['titre']) ?>">
                </div>
            <?php endforeach; ?>
            <button class="show-all-photos">
                <i class="fas fa-th"></i>
                <span>Afficher toutes les photos</span>
            </button>
        </div>

        <!-- Property Content -->
        <div class="property-content">
            <!-- Property Info -->
            <div class="property-info">
                <!-- Host Info -->
                <div class="host-info">
                    <div class="host-details">
                        <h2 class="host-title">Logement proposé par <?= htmlspecialchars($owner['username']) ?></h2>
                        <p class="host-subtitle"><?= htmlspecialchars($property['nombre_pieces']) ?> chambres · <?= htmlspecialchars($property['capacite']) ?> voyageurs</p>
                    </div>
                    <img src="<?= !empty($owner['profile_image']) ? htmlspecialchars($owner['profile_image']) : 'images/default-profile.jpg' ?>" alt="<?= htmlspecialchars($owner['username']) ?>" class="host-avatar">
                </div>

                <!-- Property Highlights -->
                <div class="property-highlights">
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="highlight-content">
                            <h3 class="highlight-title">Logement entier</h3>
                            <p class="highlight-description">Vous aurez le logement rien que pour vous.</p>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-broom"></i>
                        </div>
                        <div class="highlight-content">
                            <h3 class="highlight-title">Propre et rangé</h3>
                            <p class="highlight-description">Logement récemment nettoyé.</p>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="highlight-content">
                            <h3 class="highlight-title">Arrivée autonome</h3>
                            <p class="highlight-description">Vous pouvez entrer dans les lieux avec une boîte à clé sécurisée.</p>
                        </div>
                    </div>
                </div>

                <!-- Property Description -->
                <div class="property-description">
                    <h2 class="description-title">À propos de ce logement</h2>
                    <div class="description-content">
                        <?= nl2br(htmlspecialchars($property['description'])) ?>
                    </div>
                    <button class="show-more-button">Afficher plus</button>
                </div>

                <!-- Property Amenities -->
                <div class="property-amenities">
                    <h2 class="amenities-title">Ce que propose ce logement</h2>
                    <div class="amenities-grid">
                        <?php foreach ($amenities as $amenity): ?>
                            <?php if (!empty(trim($amenity))): ?>
                                <div class="amenity-item">
                                    <div class="amenity-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <span><?= htmlspecialchars(trim($amenity)) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <button class="show-all-amenities">Afficher les <?= count($amenities) ?> équipements</button>
                </div>
            </div>

            <!-- Booking Widget -->
            <div class="booking-widget">
                <div class="booking-header">
                    <div class="booking-price">
                        <?= htmlspecialchars($property['tarif']) ?> DA <span>nuit</span>
                    </div>
                    <div class="booking-rating">
                        <i class="fas fa-star"></i>
                        <span><?= $avg_rating ?> · <?= $review_count ?> avis</span>
                    </div>
                </div>
                <form class="booking-form" action="booking/book.php" method="GET">
                    <input type="hidden" name="id" value="<?= $property_id ?>">
                    <div class="date-picker-container">
                        <div class="date-picker-header">
                            <input type="date" name="check_in" class="date-picker-input" placeholder="Arrivée" required>
                            <input type="date" name="check_out" class="date-picker-input" placeholder="Départ" required>
                        </div>
                        <div class="guests-dropdown">
                            <select name="guests" required>
                                <option value="" disabled selected>Voyageurs</option>
                                <?php for ($i = 1; $i <= $property['capacite']; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> voyageur<?= $i > 1 ? 's' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($logged_in): ?>
                        <button type="submit" class="book-button">Réserver</button>
                    <?php else: ?>
                        <a href="logins/connexion.php" class="book-button" style="display: block; text-align: center;">Connectez-vous pour réserver</a>
                    <?php endif; ?>
                    <p class="booking-disclaimer">Vous ne serez pas débité pour le moment</p>
                    <div class="price-details">
                        <div class="price-row">
                            <div class="price-label"><?= htmlspecialchars($property['tarif']) ?> DA x 5 nuits</div>
                            <div class="price-value"><?= htmlspecialchars($property['tarif'] * 5) ?> DA</div>
                        </div>
                        <div class="price-row">
                            <div class="price-label">Frais de service</div>
                            <div class="price-value"><?= htmlspecialchars(round($property['tarif'] * 5 * 0.1)) ?> DA</div>
                        </div>
                        <div class="price-row">
                            <div class="price-label">Total</div>
                            <div class="price-value"><?= htmlspecialchars(round($property['tarif'] * 5 * 1.1)) ?> DA</div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reviews Section -->
        <?php if (!empty($reviews)): ?>
            <div class="reviews-section">
                <div class="reviews-header">
                    <h2 class="reviews-title">Avis</h2>
                    <div class="reviews-rating">
                        <i class="fas fa-star"></i>
                        <span><?= $avg_rating ?> · <?= $review_count ?> avis</span>
                    </div>
                </div>
                <div class="reviews-grid">
                    <?php 
                    $display_reviews = array_slice($reviews, 0, 6); // Display only 6 reviews
                    foreach ($display_reviews as $review): 
                    ?>
                        <div class="review-card">
                            <div class="reviewer-info">
                                <img src="<?= !empty($review['profile_image']) ? htmlspecialchars($review['profile_image']) : 'images/default-profile.jpg' ?>" alt="<?= htmlspecialchars($review['username']) ?>" class="reviewer-avatar">
                                <div class="reviewer-details">
                                    <div class="reviewer-name"><?= htmlspecialchars($review['username']) ?></div>
                                    <div class="review-date"><?= date('F Y', strtotime($review['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="review-content">
                                <?= nl2br(htmlspecialchars($review['comment'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($reviews) > 6): ?>
                    <button class="show-all-reviews">Afficher tous les <?= $review_count ?> avis</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Location Section -->
        <div class="location-section">
            <h2 class="location-title">Où se situe ce logement</h2>
            <div class="location-map">
                <iframe 
                    src="https://maps.google.com/maps?q=<?= urlencode($property['adresse']) ?>&t=&z=13&ie=UTF8&iwloc=&output=embed" 
                    frameborder="0" 
                    scrolling="no" 
                    marginheight="0" 
                    marginwidth="0">
                </iframe>
            </div>
            <p class="location-address"><?= htmlspecialchars($property['adresse']) ?></p>
        </div>

        <!-- Host Section -->
        <div class="host-section">
            <div class="host-header">
                <img src="<?= !empty($owner['profile_image']) ? htmlspecialchars($owner['profile_image']) : 'images/default-profile.jpg' ?>" alt="<?= htmlspecialchars($owner['username']) ?>" class="host-avatar-large">
                <div class="host-info-large">
                    <h2 class="host-name">Proposé par <?= htmlspecialchars($owner['username']) ?></h2>
                    <p class="host-since">Membre depuis <?= date('F Y', strtotime($owner['created_at'] ?? date('Y-m-d'))) ?></p>
                </div>
            </div>
            <div class="host-stats">
                <div class="host-stat">
                    <i class="fas fa-star"></i>
                    <span><?= $avg_rating ?> avis</span>
                </div>
                <div class="host-stat">
                    <i class="fas fa-shield-alt"></i>
                    <span>Identité vérifiée</span>
                </div>
                <div class="host-stat">
                    <i class="fas fa-home"></i>
                    <span>Superhôte</span>
                </div>
            </div>
            <p class="host-about">
                <?= !empty($owner['bio']) ? nl2br(htmlspecialchars($owner['bio'])) : 'Hôte professionnel proposant des logements de qualité en Algérie.' ?>
            </p>
            <?php if ($logged_in): ?>
                <a href="messaging/conversation.php?host_id=<?= $property['proprietaire_id'] ?>" class="contact-host">
                    <i class="fas fa-comment"></i> Contacter l'hôte
                </a>
            <?php else: ?>
                <a href="logins/connexion.php" class="contact-host">
                    <i class="fas fa-comment"></i> Connectez-vous pour contacter
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-div">
            <div>
                <h3>CONTACTS</h3>
                <p class="contact"><i class="fas fa-phone"></i> +213 712 35 46 78</p>
                <p class="contact"><i class="fas fa-envelope"></i>
                    <a class="contact" href="mailto:mnhome.dz1@gmail.com">mnhome.dz1@gmail.com</a>
                </p>
                <p class="contact"><i class="fab fa-facebook"></i> <a class="contact" href="https://www.facebook.com/profile.php?id=61575951081216">facebook.com/MN Home Dzz</a></p>
            </div>
            <div>
                <h3>PROPRIÉTÉS</h3>
                <p>● © 2025 NotreStartup</p>
                <p>● pour la location immobilière</p>
            </div>
            <div>
                <h3>CONDITIONS</h3>
                <p><a href="/conditions">Conditions Générales</a></p>
                <p><a href="/confidentialite">Politique de Confidentialité</a></p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle favorite status
        const favoriteToggle = document.querySelector('.favorite-toggle');
        if (favoriteToggle) {
            favoriteToggle.addEventListener('click', function() {
                const propertyId = this.getAttribute('data-property-id');
                
                fetch('ajax/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'property_id=' + propertyId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const icon = this.querySelector('i');
                        icon.classList.toggle('fas');
                        icon.classList.toggle('far');
                    }
                });
            });
        }

        // Show more description
        const showMoreButton = document.querySelector('.show-more-button');
        const descriptionContent = document.querySelector('.description-content');
        
        if (showMoreButton && descriptionContent) {
            showMoreButton.addEventListener('click', function() {
                descriptionContent.classList.toggle('expanded');
                this.textContent = descriptionContent.classList.contains('expanded') ? 'Afficher moins' : 'Afficher plus';
            });
        }

        // Show all amenities
        const showAllAmenitiesButton = document.querySelector('.show-all-amenities');
        
        if (showAllAmenitiesButton) {
            showAllAmenitiesButton.addEventListener('click', function() {
                // This would typically open a modal with all amenities
                alert('Cette fonctionnalité ouvrira une fenêtre modale avec tous les équipements.');
            });
        }

        // Show all photos
        const showAllPhotosButton = document.querySelector('.show-all-photos');
        
        if (showAllPhotosButton) {
            showAllPhotosButton.addEventListener('click', function() {
                // This would typically open a photo gallery modal
                alert('Cette fonctionnalité ouvrira une galerie photo en plein écran.');
            });
        }

        // Show all reviews
        const showAllReviewsButton = document.querySelector('.show-all-reviews');
        
        if (showAllReviewsButton) {
            showAllReviewsButton.addEventListener('click', function() {
                // This would typically open a modal with all reviews
                alert('Cette fonctionnalité ouvrira une fenêtre modale avec tous les avis.');
            });
        }

        // Date picker validation
        const checkInInput = document.querySelector('input[name="check_in"]');
        const checkOutInput = document.querySelector('input[name="check_out"]');
        
        if (checkInInput && checkOutInput) {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            checkInInput.min = today;
            
            checkInInput.addEventListener('change', function() {
                // Set minimum check-out date to check-in date
                checkOutInput.min = this.value;
                
                // If check-out date is before new check-in date, reset it
                if (checkOutInput.value && checkOutInput.value < this.value) {
                    checkOutInput.value = '';
                }
            });
        }
    </script>
</body>
</html>

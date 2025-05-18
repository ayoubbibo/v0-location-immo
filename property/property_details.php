<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../property/property_functions.php';
require_once '../booking/booking_functions.php';

// Check if user is logged in
$logged_in = isLoggedIn();

// Get database connection
$conn = getDbConnection();

// Get property ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get property details
$property = getPropertyById($conn, $property_id);

// Add after the line that gets property details
// Get property reviews
$reviews = getPropertyReviews($conn, $property_id);
$average_rating = getAverageRating($conn, $property_id);

// If property not found or not validated, redirect to home
if (!$property || !$property['validated']) {
    header('Location: ../index.php');
    exit;
}

// Check if property is owned by the current user
$is_owner = false;
if ($logged_in && isset($property['user_id']) && $property['user_id'] == $_SESSION['user_id']) {
    $is_owner = true;
}

// Get property photos
$photos = explode(',', $property['photos']);
$photo_urls = [];
foreach ($photos as $photo) {
    if (!empty($photo)) {
        $photo_urls[] = '../annonces/' . $photo;
    }
}

// If no photos, use default
if (empty($photo_urls)) {
    $photo_urls[] = '../images/default.jpg';
}

// Check if property is in user's favorites
$is_favorite = $logged_in ? isPropertyInFavorites($conn, $_SESSION['user_id'], $property_id) : false;

// Get today's date and default check-in/check-out dates
$today = new DateTime();
$check_in_default = $today->format('Y-m-d');
$check_out_default = $today->modify('+1 day')->format('Y-m-d');

// Calculate price for default dates
$default_price = calculateBookingPrice($conn, $property_id, $check_in_default, $check_out_default, 1);

// Process booking form submission
$booking_message = '';
$booking_success = false;

// Fix the date validation in the form submission section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_property']) && $logged_in) {
    // Validate form data
    $check_in = isset($_POST['check_in']) ? $_POST['check_in'] : '';
    $check_out = isset($_POST['check_out']) ? $_POST['check_out'] : '';
    $guests = isset($_POST['guests']) ? intval($_POST['guests']) : 1;
    
    // Validate dates
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $today = new DateTime(date('Y-m-d')); // Use only the date part
    $property_start = new DateTime($property['start_date']);
    $property_end = new DateTime($property['end_date']);
    
    if ($check_in_date < $today) {
        $booking_message = 'La date d\'arrivée ne peut pas être dans le passé.';
    } elseif ($check_out_date <= $check_in_date) {
        $booking_message = 'La date de départ doit être après la date d\'arrivée.';
    } elseif ($check_in_date < $property_start || $check_out_date > $property_end) {
        $booking_message = 'Les dates sélectionnées sont en dehors de la période de disponibilité du logement.';
    } elseif ($guests < 1 || $guests > $property['number_of_people']) {
        $booking_message = 'Le nombre de voyageurs est invalide.';
    } else {
        // Calculate total price
        $total_price = calculateBookingPrice($conn, $property_id, $check_in, $check_out, $guests);
        
        if ($total_price === false) {
            $booking_message = 'Erreur lors du calcul du prix.';
        } else {
            // Create booking
            $result = createBooking(
                $conn, 
                $property_id, 
                $_SESSION['user_id'], 
                $check_in, 
                $check_out, 
                $guests, 
                $total_price
            );
            
            $booking_success = $result['success'];
            $booking_message = $result['message'];
            
            if ($booking_success) {
                // Redirect to booking confirmation page
                header('Location: ../booking/booking_confirmation.php?id=' . $result['booking_id']);
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['title']) ?> - MN Home DZ</title>
    <link rel="stylesheet" href="./style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../images/Logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
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

    <!-- Property Details -->
    <div class="property-details">
        <!-- Property Header -->
        <div class="property-header">
            <div class="property-title">
                <h1><?= htmlspecialchars($property['title']) ?></h1>
                <p class="property-address">📍 <?= htmlspecialchars($property['address']) ?></p>
            </div>
            
            <div class="property-actions">
                <?php if ($logged_in && !$is_owner): ?>
                    <button class="heart-button <?= $is_favorite ? 'active' : '' ?>" data-property-id="<?= $property_id ?>">
                        <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-heart"></i>
                        <?= $is_favorite ? 'Retiré des favoris' : 'Ajouter aux favoris' ?>
                    </button>
                <?php endif; ?>
                
                <button class="share-button">
                    <i class="fas fa-share-alt"></i>
                    Partager
                </button>
            </div>
        </div>
        
        <!-- Property Gallery -->
        <div class="property-gallery">
            <div class="gallery-item gallery-main">
                <img src="<?= htmlspecialchars($photo_urls[0]) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
            </div>
            
            <?php for ($i = 1; $i < min(3, count($photo_urls)); $i++): ?>
                <div class="gallery-item">
                    <img src="<?= htmlspecialchars($photo_urls[$i]) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                </div>
            <?php endfor; ?>
            
            <?php if (count($photo_urls) > 3): ?>
                <div class="gallery-more">
                    <i class="fas fa-images"></i>
                    Voir toutes les photos (<?= count($photo_urls) ?>)
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Property Content -->
        <div class="property-content">
            <!-- Property Information -->
            <div class="property-info">
                <!-- Basic Info -->
                <div class="property-section">
                    <h2>À propos de ce logement</h2>
                    
                    <div class="property-features">
                        <div class="feature-item">
                            <i class="fas fa-home"></i>
                            <span><?= htmlspecialchars($property['housing_type']) ?></span>
                        </div>
                        
                        <div class="feature-item">
                            <i class="fas fa-expand-arrows-alt"></i>
                            <span><?= htmlspecialchars($property['area']) ?> m²</span>
                        </div>
                        
                        <div class="feature-item">
                            <i class="fas fa-bed"></i>
                            <span><?= htmlspecialchars($property['number_of_rooms']) ?> chambres</span>
                        </div>
                        
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>Jusqu'à <?= htmlspecialchars($property['number_of_people']) ?> personnes</span>
                        </div>
                    </div>
                    
                    <p>
                        <?= nl2br(htmlspecialchars($property['description'] ?? 'Aucune description disponible.')) ?>
                    </p>
                </div>
                
                <!-- Amenities -->
                <div class="property-section">
                    <h2>Équipements</h2>
                    
                    <div class="amenities-list">
                        <?php 
                        $amenities = explode(',', $property['amenities']);
                        foreach ($amenities as $amenity): 
                            $amenity = trim($amenity);
                            if (empty($amenity)) continue;
                        ?>
                            <div class="amenity-item">
                                <i class="fas fa-check"></i>
                                <span><?= htmlspecialchars($amenity) ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (!empty($property['other_amenities'])): ?>
                            <div class="amenity-item">
                                <i class="fas fa-plus"></i>
                                <span><?= htmlspecialchars($property['other_amenities']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Availability -->
                <div class="property-section">
                    <h2>Disponibilité</h2>
                    
                    <p>
                        Ce logement est disponible du 
                        <strong><?= date('d/m/Y', strtotime($property['start_date'])) ?></strong> 
                        au 
                        <strong><?= date('d/m/Y', strtotime($property['end_date'])) ?></strong>.
                    </p>
                </div>

                <!-- Reviews -->
                <div class="property-section reviews-section">
                    <h2>Avis (<?= count($reviews) ?>)</h2>
                    
                    <div class="reviews-header">
                        <div class="rating-summary">
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
                            <span class="average-rating"><?= number_format($average_rating, 1) ?></span>
                            <span class="review-count">(<?= count($reviews) ?> avis)</span>
                        </div>
                        
                        <?php if ($logged_in && !$is_owner): ?>
                            <button class="write-review-btn">Écrire un avis</button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): 
                                $profile_image = !empty($review['profile_image']) ? '../' . $review['profile_image'] : '../images/default-profile.jpg';
                            ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <img src="<?= htmlspecialchars($profile_image) ?>" alt="<?= htmlspecialchars($review['username']) ?>" class="reviewer-image">
                                        
                                        <div class="reviewer-info">
                                            <div class="reviewer-name"><?= htmlspecialchars($review['username']) ?></div>
                                            <div class="review-date"><?= date('d/m/Y', strtotime($review['date_avis'])) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="review-rating">
                                        <?php
                                        for ($i = 0; $i < 5; $i++) {
                                            if ($i < $review['note']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="review-content">
                                        <?= nl2br(htmlspecialchars($review['commentaire'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reviews">
                            <p>Aucun avis pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Booking Card -->
            <div class="booking-sidebar">
                <div class="booking-card">
                    <div class="booking-price">
                        <?= number_format($property['price'], 0, ',', ' ') ?> DA <span>/ nuit</span>
                    </div>
                    
                    <?php if (!empty($booking_message)): ?>
                        <div class="booking-message <?= $booking_success ? 'success' : 'error' ?>">
                            <?= htmlspecialchars($booking_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_owner): ?>
                        <div class="unavailable-message">
                            Vous ne pouvez pas réserver votre propre logement.
                        </div>
                    <?php elseif ($logged_in): ?>
                        <form class="booking-form" method="POST" action="">
                            <div class="booking-dates">
                                <div class="form-group">
                                    <label for="check_in">Arrivée</label>
                                    <input type="date" id="check_in" name="check_in" min="<?= date('Y-m-d') ?>" value="<?= $check_in_default ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="check_out">Départ</label>
                                    <input type="date" id="check_out" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= $check_out_default ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="guests">Voyageurs</label>
                                <select id="guests" name="guests" required>
                                    <?php for ($i = 1; $i <= $property['number_of_people']; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> voyageur<?= $i > 1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="booking-total">
                                <div class="price-row">
                                    <div><?= number_format($property['price'], 0, ',', ' ') ?> DA x <span id="nights-count">1</span> nuits</div>
                                    <div id="nights-total"><?= number_format($property['price'], 0, ',', ' ') ?> DA</div>
                                </div>
                                
                                <div class="total-row">
                                    <div>Total</div>
                                    <div id="booking-total"><?= number_format($default_price, 0, ',', ' ') ?> DA</div>
                                </div>
                            </div>
                            
                            <button type="submit" name="book_property" class="booking-submit">
                                Réserver
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="booking-login">
                            <p>Connectez-vous pour réserver ce logement</p>
                            <a href="../logins/connexion.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="button1">Se connecter</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle heart icon clicks (favorites)
            const heartButton = document.querySelector('.heart-button');
            if (heartButton) {
                heartButton.addEventListener('click', function() {
                    const propertyId = this.getAttribute('data-property-id');
                    
                    fetch('../ajax/toggle_favorite.php', {
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
                            if (data.action === 'added') {
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                                this.classList.add('active');
                                this.textContent = 'Retiré des favoris';
                            } else {
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                                this.classList.remove('active');
                                this.textContent = 'Ajouter aux favoris';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }
            
            // Calculate booking price
            const checkInInput = document.getElementById('check_in');
            const checkOutInput = document.getElementById('check_out');
            const guestsInput = document.getElementById('guests');
            const nightsCount = document.getElementById('nights-count');
            const nightsTotal = document.getElementById('nights-total');
            const bookingTotal = document.getElementById('booking-total');
            
            if (checkInInput && checkOutInput && guestsInput) {
                // Set property date constraints
                const propertyStartDate = new Date('<?= $property['start_date'] ?>');
                const propertyEndDate = new Date('<?= $property['end_date'] ?>');
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                // Set min dates for inputs
                const minCheckInDate = today > propertyStartDate ? today : propertyStartDate;
                checkInInput.min = minCheckInDate.toISOString().split('T')[0];
                
                // Set max dates for inputs
                const maxCheckInDate = new Date(propertyEndDate);
                maxCheckInDate.setDate(maxCheckInDate.getDate() - 1);
                checkInInput.max = maxCheckInDate.toISOString().split('T')[0];
                checkOutInput.max = propertyEndDate.toISOString().split('T')[0];
                
                const updatePrice = function() {
                    const checkIn = new Date(checkInInput.value);
                    const checkOut = new Date(checkOutInput.value);
                    
                    // Validate dates
                    let isValid = true;
                    
                    if (isNaN(checkIn.getTime()) || isNaN(checkOut.getTime())) {
                        isValid = false;
                    } else if (checkIn < today) {
                        isValid = false;
                    } else if (checkOut <= checkIn) {
                        isValid = false;
                    } else if (checkIn < propertyStartDate || checkOut > propertyEndDate) {
                        isValid = false;
                    }
                    
                    // Update booking button state
                    if (bookingSubmit) {
                        bookingSubmit.disabled = !isValid;
                    }
                    
                    if (isValid) {
                        const nights = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                        const pricePerNight = <?= $property['price'] ?>;
                        const total = nights * pricePerNight;
                        
                        nightsCount.textContent = nights;
                        nightsTotal.textContent = total.toLocaleString('fr-FR') + ' DA';
                        bookingTotal.textContent = total.toLocaleString('fr-FR') + ' DA';
                    }
                };
                
                checkInInput.addEventListener('change', function() {
                    const checkInDate = new Date(this.value);
                    const nextDay = new Date(checkInDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    
                    // Ensure next day is not after property end date
                    const minCheckOutDate = nextDay > propertyEndDate ? propertyEndDate : nextDay;
                    const minDate = minCheckOutDate.toISOString().split('T')[0];
                    checkOutInput.min = minDate;
                    
                    // If check-out date is before or equal to check-in date, update it
                    if (new Date(checkOutInput.value) <= checkInDate) {
                        checkOutInput.value = minDate;
                    }
                    
                    updatePrice();
                });
                
                checkOutInput.addEventListener('change', updatePrice);
                guestsInput.addEventListener('change', updatePrice);
                
                // Initialize on page load
                updatePrice();
            }
        });
    </script>
</body>
</html>

<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once 'property_functions.php';
require_once '../reviews/review_functions.php';

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
    die("Property not found.");
}

// Fetch reviews for the property
$reviews = getPropertyReviews($conn, $property_id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($property['title']) ?> - MN Home DZ</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="images/Logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>

    <nav class="nav-barre">
        <div class="logo-container">
            <a href="../index.php">
                <img class="Logo" src="../images/LogoBlack.png" alt="Logo" />
            </a>
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

    <main class="property-detail">
        <h1><?= htmlspecialchars($property['title']) ?></h1>
        <img src="<?= htmlspecialchars(getMainPhotoUrl($property['photos'])) ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="property-image">
        <p class="property-description"><?= nl2br(htmlspecialchars($property['description'])) ?></p>
        <p class="property-location">📍 <?= htmlspecialchars($property['address']) ?></p>
        <p class="property-price"><?= htmlspecialchars($property['price']) ?> DA/nuit</p>

        <div class="property-details">
            <span>🏠 <?= htmlspecialchars($property['area']) ?> m²</span>
            <span>🛏️ <?= htmlspecialchars($property['number_of_rooms']) ?> chambres</span>
        </div>
    </main>

    <section class="reviews-section">
        <h2>Avis des voyageurs</h2>
        <?php if (!empty($reviews)): ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <img src="<?php echo htmlspecialchars($review['profile_image']); ?>" alt="<?php echo htmlspecialchars($review['username']); ?>" class="reviewer-avatar">
                            <div class="reviewer-info">
                                <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                <div class="review-date"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Aucun avis disponible pour le moment.</p>
        <?php endif; ?>
    </section>


    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> MN Home DZ. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        // Handle favorites
        document.querySelectorAll('.heart-icon').forEach(heart => {
            heart.addEventListener('click', function() {
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
                            this.classList.toggle('fas');
                            this.classList.toggle('far');
                        }
                    });
            });
        });
    </script>
</body>

</html>
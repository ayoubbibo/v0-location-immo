<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once 'review_functions.php';

// Require login
requireLogin();

$error_message = '';
$success_message = '';

// Get review ID from URL
$review_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$review_id) {
    header("Location: ../profile/profile_dashboard.php");
    exit;
}

// Get review details
$review = getReviewDetails($conn, $review_id, $_SESSION['user_id']);

if (!$review) {
    header("Location: ../profile/profile_dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'avis - MN Home DZ</title>
    <link rel="stylesheet" href="./style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="images/Logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
</head>

<body>
    <nav class="nav-barre">
        <div>
            <a href="../index.php">
                <img class="Logo" src="../images/Logo.png" alt="Logo" />
            </a>
        </div>

        <div>
            <a href="../profile/profile_dashboard.php"><button class="button1">Mon profile</button></a>
        </div>
    </nav>

    <div class="reviews-container">
        <div class="property-header">
            <img src="<?php echo htmlspecialchars($review['main_photo']); ?>" alt="<?php echo htmlspecialchars($review['title']); ?>" class="property-image">

            <div class="property-info">
                <h1 class="property-title"><?php echo htmlspecialchars($review['title']); ?></h1>

                <p class="property-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($review['address']); ?>
                </p>

                <div class="property-rating">
                    <div class="rating-stars">
                        <?php
                        $rating = round($review['rating'] ?? 0);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>

                    <span class="rating-count">
                        <?php echo number_format($review['rating'] ?? 0, 1); ?>
                        (<?php echo $review['review_count'] ?? 0; ?> avis)
                    </span>
                </div>
            </div>

            <?php if (isLoggedIn()): ?>
                <a href="add-review.php?property_id=<?php echo $review_id; ?>" class="btn-add-review">
                    <i class="fas fa-star"></i> Ajouter un avis
                </a>
            <?php endif; ?>
        </div>

        <h2>Avis des voyageurs</h2>

        <?php if (empty($reviews)): ?>
            <div class="no-reviews">
                <i class="fas fa-comment-slash"></i>
                <h3>Aucun avis pour le moment</h3>
                <p>Soyez le premier à laisser un avis pour cette propriété.</p>

                <?php if (isLoggedIn()): ?>
                    <a href="add-review.php?property_id=<?php echo $review_id; ?>" class="btn-add-review">
                        <i class="fas fa-star"></i> Ajouter un avis
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <img src="<?php echo htmlspecialchars($review['profile_image']); ?>" alt="<?php echo htmlspecialchars($review['username']); ?>" class="reviewer-avatar">

                            <div class="reviewer-info">
                                <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
                            </div>
                        </div>

                        <div class="review-rating">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $review['rating']) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>

                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
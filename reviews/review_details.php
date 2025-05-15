<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once 'review_functions.php';

// Require login
requireLogin();

// Get database connection
$conn = getDbConnection();


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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
    <nav class="nav-barre">
        <div>
            <a href="../index.php">
                <img class="Logo" src="../images/Logo.png" alt="Logo" />
            </a>
        </div>

        <div>
            <a href="../profile/profile_dashboard.php"><button class="button1">Mon profil</button></a>
        </div>
    </nav>

    <div class="reviews-container">
        <div class="property-header">

            <div class="property-info">
                <h1 class="property-title"><?php echo htmlspecialchars($review['title']); ?></h1>
            </div>
            <img src="<?php echo htmlspecialchars($review['main_photo']); ?>" alt="<?php echo htmlspecialchars($review['title']); ?>" class="property-image">

        </div>

        <h2>Détails de l'avis</h2>

        <div class="review-card">
            <div class="review-header">
                <img src="<?php echo htmlspecialchars($review['profile_image']); ?>" alt="<?php echo htmlspecialchars($review['username']); ?>" class="reviewer-avatar">

                <div class="reviewer-info">
                    <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
                    <div class="review-date"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></div>
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
    </div>
</body>

</html>
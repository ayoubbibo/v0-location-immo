<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../reviews/review_functions.php';

// Require login
requireLogin();

$conn = getDbConnection();

$user = getUserData($conn, $_SESSION['user_id']);

if (!$user) {
    header("Location: ../logins/logout.php");
    exit;
}

// Get reviews written by user
$reviews = getUserReviews($conn, $_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Mes Avis - MN Home DZ</title>
<link rel="stylesheet" href="./style.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<link rel="icon" href="../images/Logo.png" type="image/png" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet" />
<style>
  /* Styles spécifiques pour la page */
  .reviews-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 0 1rem;
  }
  .reviews-header {
    margin-bottom: 2rem;
  }
  .reviews-header h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
  }
  .review-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }
  .review-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 1.5rem;
  }
  .review-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
  }
  .reviewer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
  }
  .reviewer-name {
    font-weight: 600;
    font-size: 1.1rem;
  }
  .review-date {
    font-size: 0.9rem;
    color: #777;
  }
  .review-rating {
    margin: 10px 0;
  }
  .review-content {
    margin-top: 10px;
    line-height: 1.5;
  }
</style>
</head>
<body>
<nav class="nav-barre">
  <div>
    <a href="../index.php"><img class="Logo" src="../images/Logo.png" alt="Logo" /></a>
  </div>
  <div>
    <a href="../profile/profile_dashboard.php"><button class="button1">Mon profil</button></a>
  </div>
</nav>

<div class="reviews-container">
  <div class="reviews-header">
    <h1>Mes Avis</h1>
  </div>
  <?php if (empty($reviews)): ?>
    <div class="empty-state" style="text-align:center; padding:2rem;">
      <i class="fas fa-star"></i>
      <h3>Vous n'avez laissé aucun avis</h3>
      <p>Commencez à laisser des avis sur vos propriétés préférées !</p>
    </div>
  <?php else: ?>
    <div class="review-list">
      <?php foreach ($reviews as $review): ?>
        <div class="review-card">
          <div class="review-header">
            <img src="<?php echo htmlspecialchars($review['profile_image']); ?>" alt="<?php echo htmlspecialchars($review['username']); ?>" class="reviewer-avatar" />
            <div>
              <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
              <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
            </div>
          </div>
          <div class="review-rating">
            <?php
            for ($i=1; $i<=5; $i++) {
              if ($i <= $review['rating']) {
                echo '<i class="fas fa-star" style="color:#f59e0b;"></i>';
              } else {
                echo '<i class="far fa-star" style="color:#ccc;"></i>';
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

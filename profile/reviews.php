<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../reviews/review_functions.php';



// Require login
requireLogin();

// Check if user is logged in
$logged_in = isLoggedIn();

// Redirect to login if not logged in
if (!$logged_in) {
    header('Location: ../logins/connexion.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

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
</head>

<body>
  <nav class="nav-barre">
    <div class="logo-container">
      <a href="../index.php"><img class="Logo" src="../images/LogoBlack.png" alt="Logo" /></a>
    </div>
    <div class="auth-buttons">
            <?php
            if ($logged_in): // Check if user is logged in
                $profile_image = !empty($user['profile_image']) ? $user['profile_image'] : '../images/default-profile.jpg';
            ?>
                <div class="user-info">
                    <a href="profile_dashboard.php" style="text-decoration: none;">
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
              for ($i = 1; $i <= 5; $i++) {
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
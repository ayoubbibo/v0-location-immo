<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../property/property_functions.php';

// Check if user is logged in
$logged_in = isLoggedIn();

// Get offset and limit parameters
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 3;

// Get database connection
$conn = getDbConnection();

// Get properties
$properties = getAllProperties($conn, $limit, $offset);

// Output HTML for each property
foreach ($properties as $property) {
    $photos = explode(',', $property['photos']);
    $photo = !empty($photos[0]) ? '../annonces/' . $photos[0] : '../images/default.jpg';
    $is_favorite = $logged_in ? isPropertyInFavorites($conn, $_SESSION['user_id'], $property['id']) : false;
    ?>
    <div class="propriete-cart">
        <div class="image-container">
            <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($property['titre']) ?>">
            <?php if ($logged_in): ?>
                <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-heart heart-icon" data-property-id="<?= $property['id'] ?>"></i>
            <?php else: ?>
                <i class="far fa-heart heart-icon" onclick="redirectToLogin()"></i>
            <?php endif; ?>
        </div>

        <div class="propriete-cont">
            <h3><?= htmlspecialchars($property['titre']) ?></h3>
            <p class="localisation">📍 <?= htmlspecialchars($property['adresse']) ?></p>
            <div class="details">
                <span>🏠 <?= htmlspecialchars($property['supperficie']) ?>m²</span>
                <span>🛏️ <?= htmlspecialchars($property['nombre_pieces']) ?> ch</span>
            </div>
            <div class="prix-row">
                <span class="prix"><?= htmlspecialchars($property['tarif']) ?> DA/nuit</span>
                <a href="../detail_bien.php?id=<?= $property['id'] ?>">
                    <button class="button4">Voir les détails</button>
                </a>
            </div>
        </div>
    </div>
    <?php
}

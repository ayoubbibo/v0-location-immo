<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../property/property_functions.php';

// Check if user is logged in
$logged_in = isLoggedIn();

// Get database connection
$conn = getDbConnection();

// Get search parameters
$criteria = [
    'address' => isset($_GET['address']) ? trim($_GET['address']) : '',
    // 'date_debut' => isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '',
    // 'date_fin' => isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '',
    // 'type_logement' => isset($_GET['type_logement']) ? trim($_GET['type_logement']) : '',
    // 'nombre_personnes' => isset($_GET['nombre_personnes']) ? intval($_GET['nombre_personnes']) : 0,
    // 'prix_min' => isset($_GET['prix_min']) ? floatval($_GET['prix_min']) : 0,
    // 'prix_max' => isset($_GET['prix_max']) ? floatval($_GET['prix_max']) : 0,
];

echo $criteria;
// Search properties
$properties = searchProperties($conn, $criteria);

// Prepare response
$response = [
    'success' => true,
    'count' => count($properties),
    'html' => '',
    'message' => count($properties) > 0 ? 
        count($properties) . ' propriété(s) trouvée(s)' : 
        'Aucune propriété ne correspond à vos critères de recherche.'
];

// Generate HTML for properties
ob_start();
if (!empty($properties)) {
    foreach ($properties as $property) {
        $photos = explode(',', $property['photos']);
        $photo = !empty($photos[0]) ? '../annonces/' . $photos[0] : '../images/default.jpg';
        $is_favorite = $logged_in ? isPropertyInFavorites($conn, $_SESSION['user_id'], $property['id']) : false;
        ?>
        <div class="propriete-cart">
            <div class="image-container">
                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                <?php if ($logged_in): ?>
                    <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-heart heart-icon" data-property-id="<?= $property['id'] ?>"></i>
                <?php else: ?>
                    <i class="far fa-heart heart-icon" onclick="redirectToLogin()"></i>
                <?php endif; ?>
            </div>

            <div class="propriete-cont">
                <h3><?= htmlspecialchars($property['title']) ?></h3>
                <p class="localisation">📍 <?= htmlspecialchars($property['address']) ?></p>
                <div class="details">
                    <span>🏠 <?= htmlspecialchars($property['area']) ?>m²</span>
                    <span>🛏️ <?= htmlspecialchars($property['number_of_rooms']) ?> ch</span>
                </div>
                <div class="prix-row">
                    <span class="prix"><?= htmlspecialchars($property['price']) ?> DA/nuit</span>
                    <a href="../property/property_details.php?id=<?= $property['id'] ?>">
                        <button class="button4">Voir les détails</button>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    ?>
    <div class="no-results">
        <i class="fas fa-search-minus"></i>
        <p>Aucune propriété ne correspond à vos critères de recherche.</p>
        <button class="reset-search-btn">Réinitialiser la recherche</button>
    </div>
    <?php
}
$response['html'] = ob_get_clean();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../property/property_functions.php';

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);

// Get database connection
$conn = getDbConnection();

// Get search parameters
$criteria = [
    'address' => isset($_GET['address']) ? trim($_GET['address']) : '',
    'check_in' => isset($_GET['check_in']) ? trim($_GET['check_in']) : '',
    'check_out' => isset($_GET['check_out']) ? trim($_GET['check_out']) : '',
    'housing_type' => isset($_GET['housing_type']) ? trim($_GET['housing_type']) : '',
    'number_of_people' => isset($_GET['number_of_people']) ? intval($_GET['number_of_people']) : 0,
    'min_price' => isset($_GET['min_price']) ? intval($_GET['min_price']) : 0,
    'max_price' => isset($_GET['max_price']) ? intval($_GET['max_price']) : 0,
    'number_of_rooms' => isset($_GET['number_of_rooms']) ? intval($_GET['number_of_rooms']) : 0,
    'amenities' => isset($_GET['amenities']) ? explode(',', $_GET['amenities']) : [],
    'min_rating' => isset($_GET['min_rating']) ? floatval($_GET['min_rating']) : 0,
    'order_by' => isset($_GET['order_by']) ? $_GET['order_by'] : 'id',
    'order_dir' => isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC',
    'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 0,
    'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0
];

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
        $photo = getMainPhotoUrl($property['photos']);
        $is_favorite = $logged_in ? isPropertyInFavorites($conn, $_SESSION['user_id'], $property['id']) : false;
        ?>
        <div class="property-card">
            <div class="image-container">
                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                <?php if ($logged_in): ?>
                    <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-heart heart-icon" data-property-id="<?= $property['id'] ?>"></i>
                <?php else: ?>
                    <i class="far fa-heart heart-icon" onclick="redirectToLogin()"></i>
                <?php endif; ?>
            </div>

            <div class="property-content">
                <h3><?= htmlspecialchars($property['title']) ?></h3>
                <p class="location">📍 <?= htmlspecialchars($property['address']) ?></p>
                <div class="details">
                    <span>🏠 <?= htmlspecialchars($property['area']) ?>m²</span>
                    <span>🛏️ <?= htmlspecialchars($property['number_of_rooms']) ?> ch</span>
                    <span>👥 <?= htmlspecialchars($property['number_of_people']) ?> personne(s)</span>
                </div>
                <div class="price-row">
                    <span class="price"><?= htmlspecialchars($property['price']) ?> DA/nuit</span>
                    <a href="../property/property_details.php?id=<?= $property['id'] ?>">
                        <button class="view-details-btn">Voir les détails</button>
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

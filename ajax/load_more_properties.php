<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once '../property/property_functions.php';

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);

// Get database connection
$conn = getDbConnection();

// Get offset and limit from GET parameters
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 6;

// Get properties
$properties = getAllProperties($conn, $limit, $offset);

// Prepare response
$response = [
    'success' => true,
    'count' => count($properties),
    'html' => ''
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
                    <span>🛏️ <?= htmlspecialchars($property['number_of_rooms']) ?> rooms</span>
                    <span>👥 <?= htmlspecialchars($property['number_of_people']) ?> people</span>
                </div>
                <div class="price-row">
                    <span class="price"><?= htmlspecialchars($property['price']) ?> DA/night</span>
                    <a href="property-details.php?id=<?= $property['id'] ?>">
                        <button class="view-details-btn">View Details</button>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}
$response['html'] = ob_get_clean();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

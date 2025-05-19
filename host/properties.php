<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';

// Require login
requireLogin();

// Get database connection
$conn = getDbConnection();

// Check if user is a host or admin
$user = getUserData($conn, $_SESSION['user_id']);
if ($user['user_type'] != 'host' && $user['user_type'] != 'admin') {
    header("Location: ../profile/profile_dashboard.php");
    exit;
}

// Get host properties
$properties_sql = "SELECT * FROM properties WHERE user_id = ? ORDER BY created_at DESC";
$properties_stmt = $conn->prepare($properties_sql);
$properties_stmt->bind_param("i", $_SESSION['user_id']);
$properties_stmt->execute();
$properties_result = $properties_stmt->get_result();
$properties = [];
while ($row = $properties_result->fetch_assoc()) {
    // Process photos
    $photos = explode(',', $row['photos']);

    if (!empty($photos[0])) {
        if (strpos($photos[0], 'http') === 0) {
            $row['main_photo'] = $photos[0];
        } else {
            $row['main_photo'] = '../images/' . $photos[0];
        }
    } else {
        $row['main_photo'] = '../images/default.jpg';
    }
    
    $properties[] = $row;
}

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $property_id = intval($_POST['property_id']);
    
    // Check if property belongs to user
    $check_sql = "SELECT id FROM properties WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $property_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Check if property has bookings
        $bookings_sql = "SELECT id FROM bookings WHERE property_id = ? AND status IN ('pending', 'confirmed')";
        $bookings_stmt = $conn->prepare($bookings_sql);
        $bookings_stmt->bind_param("i", $property_id);
        $bookings_stmt->execute();
        $bookings_result = $bookings_stmt->get_result();
        
        if ($bookings_result->num_rows > 0) {
            $error_message = 'Impossible de supprimer cette propriété car elle a des réservations en cours.';
        } else {
            // Delete property
            $delete_sql = "DELETE FROM properties WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $property_id);
            
            if ($delete_stmt->execute()) {
                $success_message = 'La propriété a été supprimée avec succès.';
                
                // Refresh properties list
                header("Location: properties.php");
                exit;
            } else {
                $error_message = 'Erreur lors de la suppression de la propriété: ' . $conn->error;
            }
        }
    } else {
        $error_message = 'Vous n\'êtes pas autorisé à supprimer cette propriété.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes propriétés - MN Home DZ</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../images/Logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .property-image {
            height: 200px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        
        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .property-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .property-content {
            padding: 1.5rem;
        }
        
        .property-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .property-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .property-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #555;
        }
        
        .property-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff385c;
            margin-bottom: 1rem;
        }
        
        .property-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {

            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-view {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-view:hover {
            background: #d0d0d0;
        }
        
        .btn-edit {
            background: #4a6ee0;
            color: white;
        }
        
        .btn-edit:hover {
            background: #3a5ecc;
        }
        
        .btn-delete {
            background: #fff0f0;
            color: #e53935;
            border: 1px solid #ffcdd2;
        }
        
        .btn-delete:hover {
            background: #ffebee;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .modal-body {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .modal-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .modal-btn-cancel {
            background: #e0e0e0;
            color: #333;
            border: none;
        }
        
        .modal-btn-cancel:hover {
            background: #d0d0d0;
        }
        
        .modal-btn-confirm {
            background: #e53935;
            color: white;
            border: none;
        }
        
        .modal-btn-confirm:hover {
            background: #c62828;
        }
        
        @media (max-width: 768px) {
            .properties-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <nav class="nav-barre">
        <div class="logo-container">
            <a href="../index.php">
                <img class="Logo" src="../images/LogoBlack.png" alt="Logo" />
            </a>
        </div>

        <div>
            <?php if (isLoggedIn()): ?>
                <a href="../profile/profile_dashboard.php"><button class="button1">Mon Compte</button></a>
                <a href="../logins/logout.php"><button class="button2">Déconnexion</button></a>
            <?php else: ?>
                <a href="../logins/connexion.php"><button class="button1">Connexion</button></a>
                <a href="../logins/formulaire.php"><button class="button2">Créer un compte</button></a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Mes propriétés</h1>
            <a href="add-property.php" class="btn-action btn-primary">
                <i class="fas fa-plus"></i> Ajouter une propriété
            </a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($properties)): ?>
            <div class="empty-state">
                <i class="fas fa-home"></i>
                <h3>Aucune propriété</h3>
                <p>Vous n'avez pas encore ajouté de propriété.</p>
                <a href="add-property.php" class="btn-action btn-primary">
                    <i class="fas fa-plus"></i> Ajouter une propriété
                </a>
            </div>
        <?php else: ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <div class="property-image">
                            <img src="<?php echo htmlspecialchars($property['main_photo']); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                            
                            <?php if ($property['validated']): ?>
                                <span class="property-status status-active">Active</span>
                            <?php else: ?>
                                <span class="property-status status-inactive">Inactive</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="property-content">
                            <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                            
                            <div class="property-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($property['address']); ?></span>
                            </div>
                            
                            <div class="property-details">
                                <div class="detail-item">
                                    <i class="fas fa-home"></i>
                                    <span><?php echo ucfirst(htmlspecialchars($property['housing_type'])); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-expand-arrows-alt"></i>
                                    <span><?php echo htmlspecialchars($property['area']); ?> m²</span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-bed"></i>
                                    <span><?php echo htmlspecialchars($property['number_of_rooms']); ?> pièces</span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo htmlspecialchars($property['number_of_people']); ?> personnes</span>
                                </div>
                            </div>
                            
                            <div class="property-price">
                                <?php echo number_format($property['price']); ?> DA / nuit
                            </div>
                            
                            <div class="property-actions">
                                <a href="../property/property_details.php?id=<?php echo $property['id']; ?>" class="btn-action btn-view">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                                
                                <a href="../property/edit_property.php?id=<?php echo $property['id']; ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                
                                <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars(addslashes($property['title'])); ?>')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Confirmer la suppression</h2>
            </div>
            
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer la propriété "<span id="propertyTitle"></span>" ?</p>
                <p>Cette action est irréversible.</p>
            </div>
            
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Annuler</button>
                
                <form id="deleteForm" method="POST" action="properties.php">
                    <input type="hidden" name="property_id" id="propertyId">
                    <input type="hidden" name="delete_property" value="1">
                    <button type="submit" class="modal-btn modal-btn-confirm">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(propertyId, propertyTitle) {
            document.getElementById('propertyId').value = propertyId;
            document.getElementById('propertyTitle').textContent = propertyTitle;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
</body>

</html>

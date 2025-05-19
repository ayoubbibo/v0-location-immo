<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';

// Require login
requireLogin();

// Get database connection
$conn = getDbConnection();

// Get property ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$property_id) {
    header("Location: ../host/properties.php");
    exit;
}

// Check if property belongs to user
$property_sql = "SELECT * FROM properties WHERE id = ? AND user_id = ?";
$property_stmt = $conn->prepare($property_sql);
$property_stmt->bind_param("ii", $property_id, $_SESSION['user_id']);
$property_stmt->execute();
$property_result = $property_stmt->get_result();

if ($property_result->num_rows === 0) {
    header("Location: ../host/properties.php");
    exit;
}

$property = $property_result->fetch_assoc();

// Process form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $housing_type = trim($_POST['housing_type'] ?? '');
    $area = intval($_POST['area'] ?? 0);
    $number_of_rooms = intval($_POST['number_of_rooms'] ?? 0);
    $number_of_people = intval($_POST['number_of_people'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
    $other_amenities = trim($_POST['other_amenities'] ?? '');
    $validated = isset($_POST['validated']) ? 1 : 0;
    
    // Validate form data
    if (empty($title)) {
        $error_message = 'Le titre est obligatoire.';
    } elseif (empty($description)) {
        $error_message = 'La description est obligatoire.';
    } elseif (empty($address)) {
        $error_message = 'L\'adresse est obligatoire.';
    } elseif (empty($housing_type)) {
        $error_message = 'Le type de logement est obligatoire.';
    } elseif ($area <= 0) {
        $error_message = 'La superficie doit être supérieure à 0.';
    } elseif ($number_of_rooms <= 0) {
        $error_message = 'Le nombre de pièces doit être supérieur à 0.';
    } elseif ($number_of_people <= 0) {
        $error_message = 'Le nombre de personnes doit être supérieur à 0.';
    } elseif ($price <= 0) {
        $error_message = 'Le prix doit être supérieur à 0.';
    } elseif (empty($start_date) || empty($end_date)) {
        $error_message = 'Les dates de disponibilité sont obligatoires.';
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error_message = 'La date de fin doit être après la date de début.';
    } else {
        // Handle photo uploads
        $photos = explode(',', $property['photos']);
        $upload_dir = '../annonces/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Handle photo deletions
        if (isset($_POST['delete_photos']) && is_array($_POST['delete_photos'])) {
            foreach ($_POST['delete_photos'] as $index) {
                if (isset($photos[$index])) {
                    $photo_path = $upload_dir . $photos[$index];
                    if (file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                    unset($photos[$index]);
                }
            }
            $photos = array_values($photos); // Reindex array
        }
        
        // Handle new photo uploads
        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
            $file_count = count($_FILES['photos']['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                $file_name = $_FILES['photos']['name'][$i];
                $file_tmp = $_FILES['photos']['tmp_name'][$i];
                $file_error = $_FILES['photos']['error'][$i];
                
                if ($file_error === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_ext, $allowed_exts)) {
                        $new_file_name = uniqid() . '.' . $file_ext;
                        $destination = $upload_dir . $new_file_name;
                        
                        if (move_uploaded_file($file_tmp, $destination)) {
                            $photos[] = $new_file_name;
                        }
                    }
                }
            }
        }
        
        $photos_str = implode(',', $photos);
        
        // Update property in database
        $sql = "UPDATE properties SET 
                title = ?, description = ?, address = ?, housing_type = ?, 
                area = ?, number_of_rooms = ?, number_of_people = ?, 
                price = ?, start_date = ?, end_date = ?, 
                amenities = ?, other_amenities = ?, photos = ?, 
                validated = ?, updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssiidssssssii",
            $title, $description, $address, $housing_type,
            $area, $number_of_rooms, $number_of_people,
            $price, $start_date, $end_date,
            $amenities, $other_amenities, $photos_str,
            $validated, $property_id
        );
        
        if ($stmt->execute()) {
            $success_message = 'La propriété a été mise à jour avec succès.';
            
            // Refresh property data
            $property_stmt->execute();
            $property_result = $property_stmt->get_result();
            $property = $property_result->fetch_assoc();
        } else {
            $error_message = 'Erreur lors de la mise à jour de la propriété: ' . $conn->error;
        }
    }
}

// Get existing amenities as array
$property_amenities = explode(',', $property['amenities']);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la propriété - MN Home DZ</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../images/Logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h1 {
            font-size: 2rem;
            color: #333;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .form-section h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: #ff385c;
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .amenities-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .amenity-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .photo-upload {
            margin-bottom: 1rem;
        }
        
        .photo-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .preview-item {
            position: relative;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .btn-submit {
            background: #ff385c;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #e0314d;
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
        
        .status-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            background: #f9fafb;
        }
        
        .toggle-label {
            font-weight: 600;
            color: #333;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #4CAF50;
        }
        
        input:focus + .toggle-slider {
            box-shadow: 0 0 1px #4CAF50;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .toggle-status {
            font-weight: 600;
        }
        
        .status-active {
            color: #4CAF50;
        }
        
        .status-inactive {
            color: #ccc;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
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
        <div class="form-header">
            <h1>Modifier la propriété</h1>
            <p>Mettez à jour les informations de votre propriété</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="edit_property.php?id=<?php echo $property_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="status-toggle">
                    <span class="toggle-label">Statut de la propriété:</span>
                    
                    <label class="toggle-switch">
                        <input type="checkbox" name="validated" <?php echo $property['validated'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    
                    <span class="toggle-status <?php echo $property['validated'] ? 'status-active' : 'status-inactive'; ?>" id="statusText">
                        <?php echo $property['validated'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                
                <div class="form-section">
                    <h2>Informations générales</h2>

                    <div class="form-group">
                        <label for="title">Titre de l'annonce *</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($property['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="address">Adresse *</label>
                        <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($property['address']); ?>" required>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Caractéristiques du logement</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="housing_type">Type de logement *</label>
                            <select id="housing_type" name="housing_type" class="form-control" required>
                                <option value="">Sélectionnez</option>
                                <option value="appartement" <?php echo $property['housing_type'] === 'appartement' ? 'selected' : ''; ?>>Appartement</option>
                                <option value="maison" <?php echo $property['housing_type'] === 'maison' ? 'selected' : ''; ?>>Maison</option>
                                <option value="villa" <?php echo $property['housing_type'] === 'villa' ? 'selected' : ''; ?>>Villa</option>
                                <option value="studio" <?php echo $property['housing_type'] === 'studio' ? 'selected' : ''; ?>>Studio</option>
                                <option value="autre" <?php echo $property['housing_type'] === 'autre' ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="area">Superficie (m²) *</label>
                            <input type="number" id="area" name="area" class="form-control" min="1" value="<?php echo htmlspecialchars($property['area']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="number_of_rooms">Nombre de pièces *</label>
                            <input type="number" id="number_of_rooms" name="number_of_rooms" class="form-control" min="1" value="<?php echo htmlspecialchars($property['number_of_rooms']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="number_of_people">Capacité (personnes) *</label>
                            <input type="number" id="number_of_people" name="number_of_people" class="form-control" min="1" value="<?php echo htmlspecialchars($property['number_of_people']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Tarification et disponibilité</h2>

                    <div class="form-group">
                        <label for="price">Prix par nuit (DA) *</label>
                        <input type="number" id="price" name="price" class="form-control" min="1" value="<?php echo htmlspecialchars($property['price']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Date de début de disponibilité *</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($property['start_date']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="end_date">Date de fin de disponibilité *</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($property['end_date']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Équipements</h2>

                    <div class="amenities-list">
                        <div class="amenity-item">
                            <input type="checkbox" id="wifi" name="amenities[]" value="WiFi" <?php echo in_array('WiFi', $property_amenities) ? 'checked' : ''; ?>>
                            <label for="wifi">WiFi</label>
                        </div>

                        <div class="amenity-item">
                            <input type="checkbox" id="tv" name="amenities[]" value="TV" <?php echo in_array('TV', $property_amenities) ? 'checked' : ''; ?>>
                            <label for="tv">TV</label>
                        </div>

                        <div class="amenity-item">
                            <input type="checkbox" id="kitchen" name="amenities[]" value="Cuisine équipée" <?php echo in_array('Cuisine équipée', $property_amenities) ? 'checked' : ''; ?>>
                            <label for="kitchen">Cuisine équipée</label>
                        </div>

                        <div class="amenity-item">
                            <input type="checkbox" id="washing_machine" name="amenities[]" value="Machine à laver" <?php echo in_array('Machine à laver', $property_amenities) ? 'checked' : ''; ?>>
                            <label for="washing_machine">Machine à laver</label>
                        </div>

                        <div class="amenity-item">
                            <input type="checkbox" id="air_conditioning" name="amenities[]" value="Climatisation" <?php echo in_array('Climatisation', $property_amenities) ? 'checked' : ''; ?>>
                            <label for="air_conditioning">Climatisation</label>
                        </div>

                        <div class="amenity-item">
                            <input type="checkbox" id="heating" name="amenities[]" value="Chauffage" <?php echo in_array('Chauffage', $property_amenities) ? 'checked' : ''; ?>>
                            <label for="heating">Chauffage</label>
                        </div>

                        <div class="amenity-item">
                            <input type="checkbox" id="parking" name="amenities[]" value="Parking" <?php echo in_array('Parking', $property_amenities) ? 'checked' : ''; ?>>
                            <label for="parking">Parking</label>
                        </div>

                        <div class="amenity-item">
                            <input type="checkbox" id="balcony" name="amenities[]" value="Balcon" <?php echo in_array('Balcon', $property_amenities) ? 'checked' : ''; ?>>
                            <label for="balcony">Balcon</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="other_amenities">Autres équipements</label>
                        <textarea id="other_amenities" name="other_amenities" class="form-control" rows="3"><?php echo htmlspecialchars($property['other_amenities']); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Photos</h2>

                    <div class="form-group">
                        <label for="photos">Ajouter de nouvelles photos</label>
                        <input type="file" id="photos" name="photos[]" class="form-control" accept="image/*" multiple>
                        <small>Formats acceptés: JPG, JPEG, PNG, GIF. Taille maximale: 5 MB par image.</small>
                    </div>

                    <div class="photo-preview" id="existing-photos">
                        <?php
                        $photos = explode(',', $property['photos']);
                        foreach ($photos as $index => $photo):
                            if (empty($photo)) continue;
                            $photo_url = strpos($photo, 'http') === 0 ? $photo : '../annonces/' . $photo;
                        ?>
                            <div class="preview-item">
                                <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Photo <?php echo $index + 1; ?>">
                                <div class="preview-remove" data-index="<?php echo $index; ?>">
                                    <i class="fas fa-times"></i>
                                </div>
                                <input type="hidden" name="delete_photos[]" value="<?php echo $index; ?>" disabled>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="photo-preview" id="new-photos"></div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-submit">Mettre à jour la propriété</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle status toggle
            const statusToggle = document.querySelector('input[name="validated"]');
            const statusText = document.getElementById('statusText');
            
            statusToggle.addEventListener('change', function() {
                if (this.checked) {
                    statusText.textContent = 'Active';
                    statusText.className = 'toggle-status status-active';
                } else {
                    statusText.textContent = 'Inactive';
                    statusText.className = 'toggle-status status-inactive';
                }
            });
            
            // Handle date validation
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            startDateInput.addEventListener('change', function() {
                const startDate = new Date(this.value);
                const nextDay = new Date(startDate);
                nextDay.setDate(nextDay.getDate() + 1);
                
                const minDate = nextDay.toISOString().split('T')[0];
                
                if (endDateInput.value && new Date(endDateInput.value) <= startDate) {
                    endDateInput.value = minDate;
                }
            });
            
            // Handle existing photo deletion
            const existingPhotos = document.getElementById('existing-photos');
            
            existingPhotos.addEventListener('click', function(e) {
                if (e.target.closest('.preview-remove')) {
                    const removeBtn = e.target.closest('.preview-remove');
                    const previewItem = removeBtn.parentElement;
                    const deleteInput = previewItem.querySelector('input[name="delete_photos[]"]');
                    
                    previewItem.classList.toggle('marked-for-deletion');
                    deleteInput.disabled = !deleteInput.disabled;
                    
                    if (previewItem.classList.contains('marked-for-deletion')) {
                        previewItem.style.opacity = '0.5';
                    } else {
                        previewItem.style.opacity = '1';
                    }
                }
            });
            
            // Handle new photo preview
            const photoInput = document.getElementById('photos');
            const photoPreview = document.getElementById('new-photos');
            
            photoInput.addEventListener('change', function() {
                photoPreview.innerHTML = '';
                
                if (this.files) {
                    const maxFiles = 5;
                    const files = Array.from(this.files).slice(0, maxFiles);
                    
                    files.forEach(function(file, index) {
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                const previewItem = document.createElement('div');
                                previewItem.className = 'preview-item';
                                
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                
                                const removeBtn = document.createElement('div');
                                removeBtn.className = 'preview-remove';
                                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                                removeBtn.dataset.index = index;
                                
                                previewItem.appendChild(img);
                                previewItem.appendChild(removeBtn);
                                photoPreview.appendChild(previewItem);
                                
                                removeBtn.addEventListener('click', function() {
                                    // Note: This doesn't actually remove the file from the input
                                    // It just hides the preview
                                    previewItem.remove();
                                });
                            };
                            
                            reader.readAsDataURL(file);
                        }
                    });
                    
                    if (this.files.length > maxFiles) {
                        alert(`Vous ne pouvez télécharger que ${maxFiles} photos maximum.`);
                    }
                }
            });
        });
    </script>
</body>

</html>

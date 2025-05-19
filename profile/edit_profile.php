<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';

requireLogin();

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Récupérer les données utilisateur
$user = getUserData($conn, $user_id);

// Stocker l'ancienne image pour affichage côte à côte
$old_image = $user['profile_image'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $profile_image_path = null;

    // Gérer l'upload de l'image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = basename($_FILES['profile_image']['name']);
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = $_FILES['profile_image']['type'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = '../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = uniqid('profile_', true) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $profile_image_path = $destPath;
            } else {
                $error = "Erreur lors du téléchargement de l'image.";
            }
        } else {
            $error = "Type de fichier non autorisé.";
        }
    }

    // Mettre à jour le profil
    $result = updateUserProfile($conn, $user_id, $username, $phone, $profile_image_path);
    if ($result['success']) {
        // Recharge les données utilisateur pour avoir la nouvelle image
        $user = getUserData($conn, $user_id);
        // Redirection pour rafraîchir la page et afficher la nouvelle image
        header('Location: profile_dashboard.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modifier le profil - MN Home DZ</title>
    <link rel="stylesheet" href="./style.css" />
</head>

<body>

    <h1 class="page-title">Modifier mon profil</h1>

    <div class="form-wrapper">
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form class="profile-form" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username" class="label">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" class="input" value="<?php echo htmlspecialchars($user['username']); ?>" required />
            </div>
            <div class="form-group">
                <label for="phone" class="label">Téléphone</label>
                <input type="tel" id="phone" name="phone" class="input" value="<?php echo htmlspecialchars($user['phone']); ?>" required />
            </div>
            
            <div class="form-group">
                <label for="profile_image" class="label">Photo de profil</label>
                <input type="file" id="profile_image" name="profile_image" class="input" accept="image/*" />
            </div>

            <div class="images-container">
                <div class="image-box">
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Ancienne photo" id="oldImage" class="avatar-preview" />
                </div>
            </div>

            <!-- Ajoutez ce script juste avant la fermeture </body> -->
            <script>
                document.getElementById('profile_image').addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    const preview = document.getElementById('oldImage');

                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block'; // Afficher l'image
                        }
                        reader.readAsDataURL(file);
                    } else {
                        preview.src = '';
                        preview.style.display = 'none'; // Cacher si pas de fichier
                    }
                });
            </script>

            <button type="submit" class="btn-submit">Enregistrer</button>
        </form>
    </div>

</body>

</html>
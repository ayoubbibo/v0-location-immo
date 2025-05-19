<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';


// Get database connection
$conn = getDbConnection();


$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $user_type = $_POST['user_type'] ?? 'guest';

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($phone) || empty($user_type)) {
        $error_message = 'Tous les champs sont obligatoires.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Veuillez entrer une adresse email valide.';
    } else {
        // Register user
        $result = registerUser($conn, $username, $email, $password, $phone, $user_type);

        if ($result['success']) {
            $success_message = 'Inscription réussie! Vous pouvez maintenant vous connecter.';
            // Redirect to login page after 2 seconds
            header("refresh:2;url=connexion.php");
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - MN Home DZ</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

</head>

<body>
    <div class="form-container">
        <div class="logo-container">
            <img class="Logo" src="../images/LogoBlack.png" alt="Logo" />
            <h2 class="form-title">Créer un compte</h2>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form action="formulaire.php" method="POST">
            <div class="user-type-selector">
                <div class="user-type-option selected" data-type="guest">
                    <i class="fas fa-user"></i>
                    <p>Voyageur</p>
                </div>
                <div class="user-type-option" data-type="host">
                    <i class="fas fa-home"></i>
                    <p>Hôte</p>
                </div>
            </div>
            <input type="hidden" name="user_type" id="user_type" value="guest">

            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn-submit">Créer mon compte</button>

            <div class="form-footer">
                <p>Vous avez déjà un compte? <a href="connexion.php">Connectez-vous</a></p>
            </div>
        </form>
    </div>

    <script>
        // User type selector
        const userTypeOptions = document.querySelectorAll('.user-type-option');
        const userTypeInput = document.getElementById('user_type');

        userTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                userTypeOptions.forEach(opt => opt.classList.remove('selected'));

                // Add selected class to clicked option
                this.classList.add('selected');

                // Update hidden input value
                userTypeInput.value = this.dataset.type;
                console.log('User type set to:', this.dataset.type); // pour tester

            });
        });
    </script>
</body>

</html>
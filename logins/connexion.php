<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';

// Get database connection
$conn = getDbConnection();

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs.';
    } else {
        $result = loginUser($conn, $email, $password);
        
        if ($result['success']) {
            // Redirect based on user type
            if ($_SESSION['user_type'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($_SESSION['user_type'] === 'host') {
                header("Location: ../host/dashboard.php");
            } else {
                header("Location: ../index.php");
            }
            exit;
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
    <title>Connexion - MN Home DZ</title>
    <link rel="icon" href="../images/Logo.png" type="image/png" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        
    </style>
</head>
<body>
    <!-- <nav class="nav-barre">
        <div>
            <a href="../index.php">
                <img class="Logo" src="../../images/Logo.png" alt="Logo" />
            </a>
        </div>
    </nav> -->
    
    <div class="form-container">
        <h2 class="form-title">Connexion</h2>
        
        <?php if ($error_message): ?>
            <div class="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form action="connexion.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Se souvenir de moi</label>
            </div>
            
            <div class="forgot-password">
                <a href="mot-de-passe-oublie.php">Mot de passe oublié?</a>
            </div>
            
            <button type="submit" class="btn-submit">Se connecter</button>
            
            <div class="form-footer">
                <p>Vous n'avez pas de compte? <a href="formulaire.php">Inscrivez-vous</a></p>
            </div>
        </form>
    </div>
</body>
</html>

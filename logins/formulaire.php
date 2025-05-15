<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';

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
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($phone)) {
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
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 120px auto 50px;
            padding: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 30px;
            color: #354464;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4b5563;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: #5D76A9;
            outline: none;
        }
        
        .btn-submit {
            width: 100%;
            background-color: #5D76A9;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #4a5d8a;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
        
        .form-footer a {
            color: #5D76A9;
            text-decoration: none;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .user-type-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-type-option.selected {
            border-color: #5D76A9;
            background-color: rgba(93, 118, 169, 0.1);
        }
        
        .user-type-option i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #5D76A9;
        }
    </style>
</head>
<body>
    <nav class="nav-barre">
        <div>
            <a href="../index.php">
                <img class="Logo" src="../images/Logo.png" alt="Logo" />
            </a>
        </div>
    </nav>
    
    <div class="form-container">
        <h2 class="form-title">Créer un compte</h2>
        
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
            });
        });
    </script>
</body>
</html>

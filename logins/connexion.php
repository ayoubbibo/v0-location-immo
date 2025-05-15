<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';

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
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 450px;
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
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input {
            margin-right: 10px;
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: #5D76A9;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .social-login {
            margin-top: 30px;
            text-align: center;
        }
        
        .social-login p {
            margin-bottom: 15px;
            color: #6b7280;
            position: relative;
        }
        
        .social-login p::before,
        .social-login p::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 35%;
            height: 1px;
            background-color: #e5e7eb;
        }
        
        .social-login p::before {
            left: 0;
        }
        
        .social-login p::after {
            right: 0;
        }
        
        .social-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #f3f4f6;
            color: #4b5563;
            font-size: 20px;
            transition: all 0.3s;
        }
        
        .social-button:hover {
            background-color: #e5e7eb;
            transform: translateY(-2px);
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
        <h2 class="form-title">Connexion</h2>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
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
            
            <div class="social-login">
                <p>Ou connectez-vous avec</p>
                <div class="social-buttons">
                    <a href="#" class="social-button">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-button">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-button">
                        <i class="fab fa-twitter"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>

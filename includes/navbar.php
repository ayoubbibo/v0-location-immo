<nav class="nav-barre">
    <div class="logo-container">
        <a href="<?php echo getBaseUrl(); ?>index.php">
            <img class="Logo" src="<?php echo getBaseUrl(); ?>images/Logo.png" alt="Logo" />
        </a>
    </div>

    <div class="nav-links">
        <ul>
            <li><a href="<?php echo getBaseUrl(); ?>index.php#Accueil">Accueil</a></li>
            <li><a href="<?php echo getBaseUrl(); ?>index.php#Rechercher">Rechercher</a></li>
            <li><a href="<?php echo getBaseUrl(); ?>index.php#Propriétés">Propriétés</a></li>
        </ul>
    </div>

    <div class="auth-buttons">
        <?php
        if (isLoggedIn()): // Check if user is logged in
        ?>
            <div class="user-info">
                <a href="<?php echo getBaseUrl(); ?>profile/profile_dashboard.php">
                    <button class="button-profile">
                        <?php if (isset($_SESSION['profile_image']) && $_SESSION['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile Picture" class="profile-pic" />
                        <?php else: ?>
                            <img src="<?php echo getBaseUrl(); ?>images/default-profile.png" alt="Default Profile" class="profile-pic" />
                        <?php endif; ?>
                        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </button>
                </a>
            </div>
        <?php else: ?>
            <a href="<?php echo getBaseUrl(); ?>logins/connexion.php"><button class="button1">Connexion</button></a>
            <a href="<?php echo getBaseUrl(); ?>logins/formulaire.php"><button class="button2">Créer un compte</button></a>
        <?php endif; ?>
    </div>
</nav>

<?php
// Helper function to get base URL for relative paths
function getBaseUrl() {
    $current_path = $_SERVER['PHP_SELF'];
    $path_parts = explode('/', $current_path);
    
    // Remove the file name and the directory it's in
    array_pop($path_parts); // Remove file name
    
    // Calculate how many directories to go back
    $depth = 0;
    foreach ($path_parts as $part) {
        if ($part !== '' && $part !== 'index.php') {
            $depth++;
        }
    }
    
    $base_url = '';
    for ($i = 0; $i < $depth; $i++) {
        $base_url .= '../';
    }
    
    return $base_url;
}
?>

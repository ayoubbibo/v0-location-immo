<?php
require_once 'config.php';
require_once 'auth/auth_functions.php';
require_once 'property/property_functions.php';

// Check if user is logged in
$logged_in = isLoggedIn();

// Get database connection
$conn = getDbConnection();

// Get properties
$properties = getAllProperties($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MN Home DZ</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" href="images/Logo.png" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
  <style>
    /* Additional styles for search functionality */
    .search-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1001;
      display: none;
    }
    
    .search-loading {
      display: none;
      text-align: center;
      padding: 20px;
    }
    
    .search-loading i {
      font-size: 2rem;
      color: #ff385c;
      animation: spin 1s infinite linear;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .search-results-info {
      background-color: #fff;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      display: none;
    }
    
    .search-results-info h3 {
      margin: 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .reset-search-btn {
      background-color: #f0f0f0;
      border: none;
      padding: 8px 15px;
      border-radius: 20px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s;
    }
    
    .reset-search-btn:hover {
      background-color: #e0e0e0;
    }
    
    .no-results {
      text-align: center;
      padding: 40px 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .no-results i {
      font-size: 3rem;
      color: #ff385c;
      margin-bottom: 15px;
    }
    
    .no-results p {
      font-size: 1.2rem;
      margin-bottom: 20px;
    }
    
    .search-filters {
      display: flex;
      gap: 10px;
      overflow-x: auto;
      padding: 10px 0;
      margin-bottom: 15px;
    }
    
    .search-filter {
      background-color: #f7f7f7;
      border: 1px solid #ddd;
      border-radius: 20px;
      padding: 5px 15px;
      font-size: 0.9rem;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .search-filter i {
      cursor: pointer;
      color: #666;
    }
    
    .search-filter i:hover {
      color: #ff385c;
    }
    
    /* Enhanced search form */
    .barre-de-recherche {
      background-color: #fff;
      border-radius: 40px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
      padding: 10px;
      max-width: 900px;
      margin: 0 auto;
      position: relative;
      z-index: 10;
    }
    
    .search-form {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
    }
    
    .search-input-group {
      flex: 1;
      min-width: 150px;
      padding: 10px 15px;
      border-right: 1px solid #eee;
    }
    
    .search-input-group:last-of-type {
      border-right: none;
    }
    
    .search-input-group label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: #333;
    }
    
    .search-input-group input,
    .search-input-group select {
      width: 100%;
      border: none;
      font-size: 1rem;
      color: #333;
      background: transparent;
    }
    
    .search-input-group input:focus,
    .search-input-group select:focus {
      outline: none;
    }
    
    .search-button-container {
      padding: 0 10px;
    }
    
    .button3 {
      background-color: #ff385c;
      color: white;
      border: none;
      border-radius: 24px;
      padding: 12px 24px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s, background-color 0.2s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .button3:hover {
      background-color: #e61e4d;
      transform: scale(1.05);
    }
    
    @media (max-width: 768px) {
      .search-input-group {
        flex: 100%;
        border-right: none;
        border-bottom: 1px solid #eee;
      }
      
      .search-input-group:last-of-type {
        border-bottom: none;
      }
      
      .search-button-container {
        width: 100%;
        padding: 10px 0 0;
        display: flex;
        justify-content: center;
      }
    }
  </style>
</head>

<body>

  <nav class="nav-barre">
    <div class="logo-container">
      <a href="index.php">
        <img class="Logo" src="images/Logo.png" alt="Logo" />
      </a>
    </div>

    <div class="nav-links">
      <ul>
        <li><a href="#Accueil">Accueil</a></li>
        <li><a href="#Rechercher">Rechercher</a></li>
        <li><a href="#Propriétés">Propriétés</a></li>
      </ul>
    </div>

    <div class="auth-buttons">
      <?php
      if ($logged_in): // Check if user is logged in
        $user_data = getUserData($conn, $_SESSION['user_id']);
        $profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'images/default-profile.jpg';
      ?>
        <div class="user-info">
          <a href="profile/dashboard.php">
            <button class="button-profile">
              <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Picture" class="profile-pic" />
              <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            </button>
          </a>
        </div>
      <?php else: ?>
        <a href="logins/connexion.php"><button class="button1">Connexion</button></a>
        <a href="logins/formulaire.php"><button class="button2">Créer un compte</button></a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="image-fond">
    <div id="Rechercher" class="phrase">
      <h1 class="la-phrase">Trouvez votre logement <br />de location en Algérie.</h1>
      <h3 class="phrase2">Des milliers de Propriétés à louer dans toute l'Algérie</h3>
    </div>

    <div id="Accueil" class="barre-de-recherche">
      <form id="search-form" class="search-form">
        <div class="search-input-group">
          <label for="adresse">Destination</label>
          <input class="bdr" type="text" name="adresse" id="adresse" placeholder="Où allez-vous ?" />
        </div>
        
        <div class="search-input-group">
          <label for="date_debut">Arrivée</label>
          <input class="bdr" type="date" name="date_debut" id="date_debut" />
        </div>
        
        <div class="search-input-group">
          <label for="date_fin">Départ</label>
          <input class="bdr" type="date" name="date_fin" id="date_fin" />
        </div>
        
        <div class="search-input-group">
          <label for="type_logement">Type de logement</label>
          <select name="type_logement" id="type_logement" class="bdr">
            <option value="">Tous les types</option>
            <option value="appartement">Appartement</option>
            <option value="maison">Maison</option>
          </select>
        </div>
        
        <div class="search-input-group">
          <label for="nombre_personnes">Voyageurs</label>
          <input class="bdr" type="number" name="nombre_personnes" id="nombre_personnes" min="1" placeholder="Nombre de personnes" />
        </div>
        
        <div class="search-button-container">
          <button type="submit" class="button3">
            <i class="fas fa-search"></i>
            Rechercher
          </button>
        </div>
      </form>
    </div>
  </div>

  <section id="Proprietes" class="proprietes-section">
    <div class="search-results-info">
      <h3>
        <span id="search-results-count"></span>
        <button class="reset-search-btn">Réinitialiser la recherche</button>
      </h3>
      <div class="search-filters" id="search-filters"></div>
    </div>
    
    <div class="search-loading">
      <i class="fas fa-spinner"></i>
      <p>Recherche en cours...</p>
    </div>
    
    <h2>Nos Propriétés Disponibles</h2>
    <div class="propriete-list">
      <?php if (!empty($properties)): ?>
        <?php foreach ($properties as $property):
          $photos = explode(',', $property['photos']);
          $photo = !empty($photos[0]) ? 'annonces/' . $photos[0] : 'images/default.jpg';
          $is_favorite = $logged_in ? isPropertyInFavorites($conn, $_SESSION['user_id'], $property['id']) : false;
        ?>
          <div class="propriete-cart">
            <div class="image-container">
              <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($property['titre']) ?>">
              <?php if ($logged_in): ?>
                <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-heart heart-icon" data-property-id="<?= $property['id'] ?>"></i>
              <?php else: ?>
                <i class="far fa-heart heart-icon" onclick="redirectToLogin()"></i>
              <?php endif; ?>
            </div>

            <div class="propriete-cont">
              <h3><?= htmlspecialchars($property['titre']) ?></h3>
              <p class="localisation">📍 <?= htmlspecialchars($property['adresse']) ?></p>
              <div class="details">
                <span>🏠 <?= htmlspecialchars($property['supperficie']) ?>m²</span>
                <span>🛏️ <?= htmlspecialchars($property['nombre_pieces']) ?> ch</span>
              </div>
              <div class="prix-row">
                <span class="prix"><?= htmlspecialchars($property['tarif']) ?> DA/nuit</span>
                <a href="detail_bien.php?id=<?= $property['id'] ?>">
                  <button class="button4">Voir les détails</button>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Aucune annonce disponible pour le moment.</p>
      <?php endif; ?>
    </div>
    
    <?php
    // Check if there are more properties to load
    $total_properties = countTotalProperties($conn);
    if ($total_properties > count($properties)):
    ?>
      <div class="btn-aut">
        <button id="plus" class="button5">Autres</button>
      </div>
    <?php endif; ?>
  </section>

  <hr>

  <section>
    <div class="nous">
      <div class="text">
        <div class="titre">
          <span class="ligne"></span>
          <h4>Qui nous sommes</h4>
        </div>

        <h2>Decouvrer avec nous des vacances de luxe .</h2>
        <p>MN Home DZ est votre plateforme de confiance pour découvrir et louer des biens immobiliers en Algérie. Que vous cherchiez un appartement ou une maison nous <br> vous offrons une interface simple, rapide et intuitive pour trouver le bien qui vous correspond. Avec MN Home DZ, l'immobilier devient plus accessible, plus clair et <br> plus efficace.</p>
      </div>
      <img class="img" src="images/comment-proteger-sa-maison-sans-alarme (1).jpg" alt="">
    </div>
  </section>
  
  <div class="mission">
    <div class="text2">
      <div class="titre2">
        <span class="ligne"></span>
        <h4>Notre mission</h4>
      </div>

      <h2>Bienvenue sur MN HOME – Notre mission, votre confort.</h2>
      <p>Chez MN Home DZ, nous avons pour mission de faciliter la recherche de logements <br>adaptés aux familles, en mettant en avant des biens fiables, confortables et bien situés. <br> Notre plateforme vise à créer un lien de confiance entre les locataires et les propriétaires,<br> en simplifiant chaque étape de la location.</p>
    </div>
  </div>

  <div class="search-overlay"></div>

  <footer id="Propriétés">
    <div class="footer-div">

      <div>
        <h3>CONTACTS</h3>
        <p class="contact"><i class="fas fa-phone"></i> +213 712 35 46 78</p>
        <p class="contact"><i class="fas fa-envelope"></i>
          <a class="contact" href="https://mail.google.com/mail/?view=cm&to=mnhome.dz1@gmail.com">mnhome.dz1@gmail.com </a>
        </p>
        <p class="contact"><i class="fab fa-facebook"></i> <a class="contact" href="https://www.facebook.com/profile.php?id=61575951081216">facebook.com/MN Home Dzz</a></p>
      </div>

      <div>
        <h3>PROPRIÉTÉS</h3>
        <p>● © 2025 NotreStartup</p>
        <p>● pour la location immobiliere</p>

      </div>

      <div>
        <h3>CONDITIONS</h3>
        <p><a href="/conditions">Conditions Générales</a></p>
        <p><a href="/confidentialite">Politique de Confidentialité</a></p>

      </div>

    </div>
  </footer>

  <script>
    // Function to redirect to login page
    function redirectToLogin() {
      window.location.href = 'logins/connexion.php';
    }

    // Handle heart icon clicks (favorites)
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('heart-icon')) {
        <?php if ($logged_in): ?>
          const propertyId = e.target.getAttribute('data-property-id');
          const heartIcon = e.target;
          
          fetch('ajax/toggle_favorite.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'property_id=' + propertyId
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              heartIcon.classList.toggle('fas');
              heartIcon.classList.toggle('far');
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
        <?php else: ?>
          redirectToLogin();
        <?php endif; ?>
      }
    });

    // Load more properties
    const loadMoreBtn = document.getElementById('plus');
    if (loadMoreBtn) {
      let offset = <?= count($properties) ?>;
      const limit = 3; // Load 3 more properties at a time
      
      loadMoreBtn.addEventListener('click', function() {
        fetch('ajax/load_more_properties.php?offset=' + offset + '&limit=' + limit)
          .then(response => response.text())
          .then(html => {
            const propertyList = document.querySelector('.propriete-list');
            propertyList.insertAdjacentHTML('beforeend', html);
            
            offset += limit;
            
            // Hide button if no more properties
            if (offset >= <?= $total_properties ?>) {
              loadMoreBtn.style.display = 'none';
            }
            
            // Initialize heart icons for newly loaded properties
            document.querySelectorAll('.heart-icon:not([data-initialized])').forEach(function(heartIcon) {
              heartIcon.setAttribute('data-initialized', 'true');
            });
          });
      });
    }

    // In-page search functionality
    document.addEventListener('DOMContentLoaded', function() {
      const searchForm = document.getElementById('search-form');
      const propertyList = document.querySelector('.propriete-list');
      const searchResultsInfo = document.querySelector('.search-results-info');
      const searchResultsCount = document.getElementById('search-results-count');
      const searchFilters = document.getElementById('search-filters');
      const searchLoading = document.querySelector('.search-loading');
      const searchOverlay = document.querySelector('.search-overlay');
      const resetSearchBtn = document.querySelector('.reset-search-btn');
      const loadMoreButton = document.getElementById('plus');
      const sectionTitle = document.querySelector('.proprietes-section h2');
      
      // Function to update URL with search parameters
      function updateURL(params) {
        const url = new URL(window.location.href);
        
        // Clear existing parameters
        url.search = '';
        
        // Add new parameters
        for (const key in params) {
          if (params[key]) {
            url.searchParams.set(key, params[key]);
          }
        }
        
        // Update browser history without reloading the page
        window.history.pushState({}, '', url);
      }
      
      // Function to get search parameters from URL
      function getSearchParamsFromURL() {
        const params = new URLSearchParams(window.location.search);
        const searchParams = {};
        
        for (const [key, value] of params.entries()) {
          searchParams[key] = value;
        }
        
        return searchParams;
      }
      
      // Function to fill form with URL parameters
      function fillFormWithURLParams() {
        const params = getSearchParamsFromURL();
        
        for (const key in params) {
          const input = document.querySelector(`[name="${key}"]`);
          if (input) {
            input.value = params[key];
          }
        }
        
        // If there are search parameters, perform search
        if (Object.keys(params).length > 0) {
          performSearch(params);
        }
      }
      
      // Fill form with URL parameters on page load
      fillFormWithURLParams();
      
      // Function to display search filters
      function displaySearchFilters(params) {
        searchFilters.innerHTML = '';
        
        const filterLabels = {
          'adresse': 'Destination',
          'date_debut': 'Arrivée',
          'date_fin': 'Départ',
          'type_logement': 'Type',
          'nombre_personnes': 'Voyageurs'
        };
        
        for (const key in params) {
          if (params[key]) {
            const filterValue = key === 'type_logement' ? 
              params[key].charAt(0).toUpperCase() + params[key].slice(1) : 
              params[key];
            
            const filterLabel = filterLabels[key] || key;
            
            const filterElement = document.createElement('div');
            filterElement.className = 'search-filter';
            filterElement.innerHTML = `
              <span>${filterLabel}: ${filterValue}</span>
              <i class="fas fa-times" data-filter="${key}"></i>
            `;
            
            searchFilters.appendChild(filterElement);
          }
        }
      }
      
      // Function to perform search
      function performSearch(params) {
        // Show loading state
        searchLoading.style.display = 'block';
        searchOverlay.style.display = 'block';
        
        // Build query string
        const queryString = Object.keys(params)
          .filter(key => params[key])
          .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
          .join('&');
        
        // Fetch search results
        fetch('ajax/search_properties.php?' + queryString)
          .then(response => response.json())
          .then(data => {
            // Hide loading state
            searchLoading.style.display = 'none';
            searchOverlay.style.display = 'none';
            
            // Update property list
            propertyList.innerHTML = data.html;
            
            // Update search results info
            if (Object.keys(params).some(key => params[key])) {
              searchResultsInfo.style.display = 'block';
              searchResultsCount.textContent = data.message;
              displaySearchFilters(params);
              sectionTitle.textContent = 'Résultats de recherche';
            } else {
              searchResultsInfo.style.display = 'none';
              sectionTitle.textContent = 'Nos Propriétés Disponibles';
            }
            
            // Hide load more button if no results or all results shown
            if (loadMoreButton) {
              loadMoreButton.style.display = data.count > 0 ? 'block' : 'none';
            }
            
            // Update URL
            updateURL(params);
          })
          .catch(error => {
            console.error('Error:', error);
            searchLoading.style.display = 'none';
            searchOverlay.style.display = 'none';
          });
      }
      
      // Handle search form submission
      searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(searchForm);
        const params = {};
        
        for (const [key, value] of formData.entries()) {
          params[key] = value;
        }
        
        performSearch(params);
      });
      
      // Handle reset search button
      resetSearchBtn.addEventListener('click', function() {
        // Clear form
        searchForm.reset();
        
        // Clear URL parameters
        window.history.pushState({}, '', window.location.pathname);
        
        // Reset search
        performSearch({});
      });
      
      // Handle filter removal
      searchFilters.addEventListener('click', function(e) {
        if (e.target.tagName === 'I' && e.target.classList.contains('fa-times')) {
          const filterKey = e.target.getAttribute('data-filter');
          const params = getSearchParamsFromURL();
          
          // Remove the filter
          delete params[filterKey];
          
          // Clear the form field
          const input = document.querySelector(`[name="${filterKey}"]`);
          if (input) {
            input.value = '';
          }
          
          // Perform search with updated params
          performSearch(params);
        }
      });
    });
  </script>
</body>
</html>

<?php
require_once 'config.php';
require_once 'auth/auth_functions.php';
require_once 'property/property_functions.php';

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);

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
</head>

<body>

  <nav class="nav-barre">
    <div class="logo-container">
      <a href="index.php">
        <img class="Logo" src="images/LogoBlack.png" alt="Logo" />
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
      <?php if ($logged_in): ?>
        <div class="user-info">
          <a href="profile/profile_dashboard.php" style="text-decoration: none;">
            <button class="button-profile">
              <img src="<?php echo htmlspecialchars($_SESSION['profile_image'] ?? 'images/default-profile.jpg'); ?>" alt="Profile Picture" class="profile-pic" />
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

  <div id="Accueil" class="image-fond">
    <div id="Rechercher" class="phrase">
      <h1 class="la-phrase">Trouvez votre logement <br />de location en Algérie.</h1>
      <h3 class="phrase2">Des milliers de Propriétés à louer dans toute l'Algérie</h3>
    </div>

    <div id="Rechercher" class="search-form-container">
      <form id="search-form" class="search-form" action="javascript:void(0);">
        <div class="search-input-group">
          <label for="address">Destination</label>
          <input type="text" name="address" id="address" placeholder="Où allez-vous ?" />
        </div>

        <div class="search-input-group">
          <label for="check_in">Check-in</label>
          <input type="date" name="check_in" id="check_in" />
        </div>

        <div class="search-input-group">
          <label for="check_out">Check-out</label>
          <input type="date" name="check_out" id="check_out" />
        </div>

        <div class="search-input-group">
          <label for="housing_type">Type de logement</label>
          <select name="housing_type" id="housing_type">
            <option value="">Tous les types</option>
            <option value="appartement">Appartement</option>
            <option value="maison">Maison</option>
          </select>
        </div>

        <div class="search-input-group">
          <label for="number_of_people">Voyageurs</label>
          <input type="number" name="number_of_people" id="number_of_people" min="1" placeholder="Nombre de personnes" />
        </div>

        <div class="search-button-container">
          <button type="submit" class="search-button">
            <i class="fas fa-search"></i>
            Rechercher
          </button>
        </div>
      </form>
    </div>
  </div>

  <section id="Propriétés" class="proprietes-section">
    <div class="search-results-info">
      <h3>
        <span id="search-results-count"></span>
        <button class="reset-search-btn" onclick="resetSearch()">Réinitialiser la recherche</button>
      </h3>
      <div class="search-filters" id="search-filters"></div>
    </div>

    <div class="search-loading">
      <i class="fas fa-spinner"></i>
      <p>Recherche en cours...</p>
    </div>

    <h2>Nos Propriétés Disponibles</h2>
    <div class="property-grid">
      <?php if (!empty($properties)): ?>
        <?php foreach ($properties as $property):
          $photos = explode(',', $property['photos']);
          if (strpos($photos[0], 'http') === 0) {
            $photo = $photos[0];
          } else {
            $photo = !empty($photos[0]) ? '../images/' . $photos[0] : 'images/default.jpg';
          }


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
                <span class="price"><?= htmlspecialchars($property['price']) ?> DA/nuit</span>
                <a href="property/property_details.php?id=<?= $property['id'] ?>">
                  <button class="view-details-btn">Voir les détails</button>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No properties available at the moment.</p>
      <?php endif; ?>
    </div>

    <?php
    // Check if there are more properties to load
    $total_properties = countTotalProperties($conn);
    if ($total_properties > count($properties)):
    ?>
      <div class="load-more-container">
        <button id="load-more-btn" class="load-more-btn" data-offset="<?= count($properties) ?>">Autres</button>
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

  <footer>
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
        <p><a href="conditions.php">Conditions Générales</a></p>
        <p><a href="confidentialite.php">Politique de Confidentialité</a></p>

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
              console.error("we are here", propertyId);
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
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
      loadMoreBtn.addEventListener('click', function() {
        const offset = parseInt(this.getAttribute('data-offset'));
        const limit = 6; // Load 6 more properties at a time

        fetch('ajax/load_more_properties.php?offset=' + offset + '&limit=' + limit)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Append new properties to the grid
              document.querySelector('.property-grid').insertAdjacentHTML('beforeend', data.html);

              // Update offset
              this.setAttribute('data-offset', offset + limit);

              // Hide button if no more properties
              if (offset + limit >= <?= $total_properties ?>) {
                this.style.display = 'none';
              }
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
      });
    }

    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
      const searchForm = document.getElementById('search-form');
      const propertyGrid = document.querySelector('.property-grid');
      const searchResultsInfo = document.querySelector('.search-results-info');
      const searchResultsCount = document.getElementById('search-results-count');
      const searchFilters = document.getElementById('search-filters');
      const searchLoading = document.querySelector('.search-loading');
      const searchOverlay = document.querySelector('.search-overlay');
      const sectionTitle = document.querySelector('.proprietes-section h2');
      const loadMoreContainer = document.querySelector('.load-more-container');

      // Set minimum date for check-in to today
      const today = new Date().toISOString().split('T')[0];
      const checkInInput = document.getElementById('check_in');
      const checkOutInput = document.getElementById('check_out');

      checkInInput.min = today;
      checkOutInput.min = new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0];

      // If check-out date is already selected and check-in is empty, set check-in to today
      if (checkOutInput.value && !checkInInput.value) {
        checkInInput.value = today;
      }

      // Update check-out min date when check-in changes
      checkInInput.addEventListener('change', function() {
        const checkInDate = new Date(this.value);
        checkInDate.setDate(checkInDate.getDate() + 1);
        const minCheckOutDate = checkInDate.toISOString().split('T')[0];
        checkOutInput.min = minCheckOutDate;

        // If check-out date is before new min, update it
        if (checkOutInput.value && new Date(checkOutInput.value) < checkInDate) {
          checkOutInput.value = minCheckOutDate;
        }
      });

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
          'address': 'Destination',
          'check_in': 'Check-in',
          'check_out': 'Check-out',
          'housing_type': 'Property Type',
          'number_of_people': 'Guests',
          'min_price': 'Min Price',
          'max_price': 'Max Price',
          'number_of_rooms': 'Rooms',
          'min_rating': 'Rating'
        };

        for (const key in params) {
          if (params[key]) {
            let filterValue = params[key];

            // // Format housing_type for display
            // if (key === 'housing_type') {
            //   filterValue = filterValue.charAt(0).toUpperCase() + filterValue.slice(1);
            // }

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

            // Update property grid
            propertyGrid.innerHTML = data.html;

            // Update search results info
            if (Object.keys(params).some(key => params[key])) {
              searchResultsInfo.style.display = 'block';
              searchResultsCount.textContent = data.message;
              displaySearchFilters(params);
              sectionTitle.textContent = 'Résultats de recherche';

              // Hide load more button for search results
              if (loadMoreContainer) {
                loadMoreContainer.style.display = 'none';
              }
            } else {
              searchResultsInfo.style.display = 'none';
              sectionTitle.textContent = 'Nos Propriétés Disponibles';

              // Show load more button for all properties
              if (loadMoreContainer) {
                loadMoreContainer.style.display = 'block';
              }
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

      // Function to reset search
      window.resetSearch = function() {
        // Clear form
        searchForm.reset();

        // Clear URL parameters
        window.history.pushState({}, '', window.location.pathname);

        // Reset search
        performSearch({});
      };

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
border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .reviewer-info {
            flex: 1;
        }
        
        .reviewer-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .review-date {
            color: #6b7280;
            font-size: 14px;
        }
        
        .review-rating {
            color: #f59e0b;
            margin-bottom: 10px;
        }
        
        .review-content {
            color: #4b5563;
            line-height: 1.6;
        }
        
        .no-reviews {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .no-reviews i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 15px;
        }
        
        .no-reviews h3 {
            margin-bottom: 10px;
            color: #4b5563;
        }
        
        .no-reviews p {
            color: #6b7280;
        }
        
        .btn-add-review {
            display: inline-block;
            background-color: #5D76A9;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            margin-top: 20px;
        }
        
        .btn-add-review:hover {
            background-color: #4a5d8a;
        }
        
        @media (max-width: 768px) {
            .property-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .property-image {
                margin-bottom: 15px;
            }
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
        
        <div class="div-de-ul">
            <ul>
                <li><a href="../index.php#Accueil">Accueil</a></li>
                <li><a href="../index.php#Rechercher">Rechercher</a></li>
                <li><a href="../index.php#Propriétés">Propriétés</a></li>
            </ul>
        </div>
        
        <div>
            <?php if (isLoggedIn()): ?>
                <a href="../profile/dashboard.php"><button class="button1">Mon Compte</button></a>
                <a href="../logins/logout.php"><button class="button2">Déconnexion</button></a>
            <?php else: ?>
                <a href="../logins/connexion.php"><button class="button1">Connexion</button></a>
                <a href="../logins/formulaire.php"><button class="button2">Créer un compte</button></a>
            <?php endif; ?>
        </div>
    </nav>
    
    <div class="reviews-container">
        <div class="property-header">
            <img src="<?php echo htmlspecialchars($main_photo); ?>" alt="<?php echo htmlspecialchars($property['titre']); ?>" class="property-image">
            
            <div class="property-info">
                <h1 class="property-title"><?php echo htmlspecialchars($property['titre']); ?></h1>
                
                <p class="property-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($property['adresse']); ?>
                </p>
                
                <div class="property-rating">
                    <div class="rating-stars">
                        <?php
                        $rating = round($property['rating'] ?? 0);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    
                    <span class="rating-count">
                        <?php echo number_format($property['rating'] ?? 0, 1); ?> 
                        (<?php echo $property['review_count'] ?? 0; ?> avis)
                    </span>
                </div>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <a href="add-review.php?property_id=<?php echo $property_id; ?>" class="btn-add-review">
                    <i class="fas fa-star"></i> Ajouter un avis
                </a>
            <?php endif; ?>
        </div>
        
        <h2>Avis des voyageurs</h2>
        
        <?php if (empty($reviews)): ?>
            <div class="no-reviews">
                <i class="fas fa-comment-slash"></i>
                <h3>Aucun avis pour le moment</h3>
                <p>Soyez le premier à laisser un avis pour cette propriété.</p>
                
                <?php if (isLoggedIn()): ?>
                    <a href="add-review.php?property_id=<?php echo $property_id; ?>" class="btn-add-review">
                        <i class="fas fa-star"></i> Ajouter un avis
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <img src="<?php echo htmlspecialchars($review['profile_image']); ?>" alt="<?php echo htmlspecialchars($review['username']); ?>" class="reviewer-avatar">
                            
                            <div class="reviewer-info">
                                <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="review-rating">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $review['rating']) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        
                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once 'message_functions.php';

// Require login
requireLogin();

// Get user conversations
$conversations = getConversations($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - MN Home DZ</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .messaging-container {
            max-width: 1200px;
            margin: 120px auto 50px;
            padding: 0 20px;
        }
        
        .messaging-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .conversation-list {
            border-right: 1px solid #e5e7eb;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .conversation-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .conversation-item:hover {
            background-color: #f9fafb;
        }
        
        .conversation-item.active {
            background-color: #f3f4f6;
            border-left: 3px solid #5D76A9;
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .conversation-info {
            flex: 1;
        }
        
        .conversation-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .conversation-preview {
            color: #6b7280;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        .conversation-meta {
            text-align: right;
        }
        
        .conversation-time {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .conversation-unread {
            display: inline-block;
            background-color: #5D76A9;
            color: white;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        .message-area {
            display: flex;
            flex-direction: column;
            height: 600px;
        }
        
        .message-header {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .message-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .message-header-name {
            font-weight: 600;
            font-size: 18px;
        }
        
        .message-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f9fafb;
        }
        
        .message-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
        }
        
        .message-placeholder i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 30px;
            text-align: center;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #4b5563;
        }
        
        @media (max-width: 768px) {
            .messaging-grid {
                grid-template-columns: 1fr;
            }
            
            .conversation-list {
                max-height: none;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
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
    
    <div class="messaging-container">
        <h1>Messagerie</h1>
        
        <div class="messaging-grid">
            <div class="conversation-list">
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>Aucune conversation</h3>
                        <p>Vous n'avez pas encore de conversations.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <a href="conversation.php?id=<?php echo $conversation['conversation_id']; ?>" class="conversation-item">
                            <img src="<?php echo htmlspecialchars($conversation['other_user_image']); ?>" alt="<?php echo htmlspecialchars($conversation['other_user_name']); ?>" class="conversation-avatar">
                            
                            <div class="conversation-info">
                                <div class="conversation-name"><?php echo htmlspecialchars($conversation['other_user_name']); ?></div>
                                <div class="conversation-preview">
                                    <?php if ($conversation['last_message_sender'] == $_SESSION['user_id']): ?>
                                        <span>Vous: </span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 50)); ?>
                                    <?php if (strlen($conversation['last_message']) > 50): ?>...<?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="conversation-meta">
                                <div class="conversation-time">
                                    <?php 
                                    $message_time = strtotime($conversation['last_message_time']);
                                    $now = time();
                                    $diff = $now - $message_time;
                                    
                                    if ($diff < 60) {
                                        echo 'À l\'instant';
                                    } elseif ($diff < 3600) {
                                        echo 'Il y a ' . floor($diff / 60) . ' min';
                                    } elseif ($diff < 86400) {
                                        echo 'Il y a ' . floor($diff / 3600) . ' h';
                                    } elseif ($diff < 172800) {
                                        echo 'Hier';
                                    } else {
                                        echo date('d/m/Y', $message_time);
                                    }
                                    ?>
                                </div>
                                
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <div class="conversation-unread"><?php echo $conversation['unread_count']; ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="message-area">
                <div class="message-placeholder">
                    <i class="fas fa-comments"></i>
                    <p>Sélectionnez une conversation pour afficher les messages</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
require_once '../config.php';
require_once '../auth/auth_functions.php';
require_once 'message_functions.php';

// Require login
requireLogin();

$error_message = '';
$success_message = '';

// Get conversation ID from URL
$conversation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$conversation_id) {
    header("Location: inbox.php");
    exit;
}

// Get conversation details
$conversation = getConversationDetails($conn, $conversation_id, $_SESSION['user_id']);

if (!$conversation) {
    header("Location: inbox.php");
    exit;
}

// Get messages
$messages_result = getMessages($conn, $conversation_id, $_SESSION['user_id']);

if (!$messages_result['success']) {
    header("Location: inbox.php");
    exit;
}

$messages = $messages_result['messages'];

// Process new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_text = $_POST['message'] ?? '';
    
    if (empty($message_text)) {
        $error_message = 'Le message ne peut pas être vide.';
    } else {
        $result = sendMessage($conn, $_SESSION['user_id'], $conversation['other_user_id'], $message_text, $conversation_id);
        
        if ($result['success']) {
            // Redirect to refresh the page and avoid form resubmission
            header("Location: conversation.php?id=" . $conversation_id);
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
    <title>Conversation avec <?php echo htmlspecialchars($conversation['other_user_name']); ?> - MN Home DZ</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .messaging-container {
            max-width: 1000px;
            margin: 120px auto 50px;
            padding: 0 20px;
        }
        
        .conversation-container {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 600px;
        }
        
        .conversation-header {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .conversation-header-back {
            margin-right: 15px;
            color: #6b7280;
            font-size: 20px;
        }
        
        .conversation-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .conversation-header-name {
            font-weight: 600;
            font-size: 18px;
        }
        
        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f9fafb;
            display: flex;
            flex-direction: column;
        }
        
        .message {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 12px 15px;
            border-radius: 15px;
            position: relative;
        }
        
        .message-sender {
            align-self: flex-end;
            background-color: #5D76A9;
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .message-receiver {
            align-self: flex-start;
            background-color: #e5e7eb;
            color: #4b5563;
            border-bottom-left-radius: 5px;
        }
        
        .message-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            position: absolute;
            bottom: -5px;
        }
        
        .message-sender .message-avatar {
            right: -40px;
        }
        
        .message-receiver .message-avatar {
            left: -40px;
        }
        
        .message-time {
            font-size: 12px;
            margin-top: 5px;
            opacity: 0.8;
        }
        
        .message-form {
            display: flex;
            padding: 15px;
            border-top: 1px solid #e5e7eb;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            margin-right: 10px;
        }
        
        .message-input:focus {
            border-color: #5D76A9;
            outline: none;
        }
        
        .btn-send {
            background-color: #5D76A9;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0 20px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-send:hover {
            background-color: #4a5d8a;
        }
        
        .btn-send i {
            margin-right: 5px;
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
        
        .date-divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #6b7280;
        }
        
        .date-divider::before,
        .date-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .date-divider::before {
            margin-right: 10px;
        }
        
        .date-divider::after {
            margin-left: 10px;
        }
        
        .empty-conversation {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
            text-align: center;
        }
        
        .empty-conversation i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #d1d5db;
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
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="conversation-container">
            <div class="conversation-header">
                <a href="inbox.php" class="conversation-header-back">
                    <i class="fas fa-arrow-left"></i>
                </a>
                
                <img src="<?php echo htmlspecialchars($conversation['other_user_image']); ?>" alt="<?php echo htmlspecialchars($conversation['other_user_name']); ?>" class="conversation-header-avatar">
                
                <div class="conversation-header-name">
                    <?php echo htmlspecialchars($conversation['other_user_name']); ?>
                </div>
            </div>
            
            <div class="messages-container" id="messages-container">
                <?php if (empty($messages)): ?>
                    <div class="empty-conversation">
                        <i class="fas fa-comments"></i>
                        <h3>Aucun message</h3>
                        <p>Commencez la conversation en envoyant un message.</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $current_date = null;
                    foreach ($messages as $message): 
                        $message_date = date('Y-m-d', strtotime($message['created_at']));
                        
                        // Add date divider if date changes
                        if ($message_date !== $current_date) {
                            $current_date = $message_date;
                            
                            // Format date
                            $today = date('Y-m-d');
                            $yesterday = date('Y-m-d', strtotime('-1 day'));
                            
                            if ($message_date === $today) {
                                $display_date = 'Aujourd\'hui';
                            } elseif ($message_date === $yesterday) {
                                $display_date = 'Hier';
                            } else {
                                $display_date = date('d/m/Y', strtotime($message['created_at']));
                            }
                            
                            echo '<div class="date-divider">' . $display_date . '</div>';
                        }
                        
                        // Determine message class
                        $message_class = $message['sender_id'] == $_SESSION['user_id'] ? 'message-sender' : 'message-receiver';
                    ?>
                        <div class="message <?php echo $message_class; ?>">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            
                            <div class="message-time">
                                <?php echo date('H:i', strtotime($message['created_at'])); ?>
                            </div>
                            
                            <img src="<?php echo htmlspecialchars($message['sender_image']); ?>" alt="<?php echo htmlspecialchars($message['sender_name']); ?>" class="message-avatar">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <form class="message-form" method="POST" action="conversation.php?id=<?php echo $conversation_id; ?>">
                <input type="text" name="message" class="message-input" placeholder="Écrivez votre message..." required autofocus>
                <button type="submit" class="btn-send">
                    <i class="fas fa-paper-plane"></i>
                    Envoyer
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Scroll to bottom of messages container
        const messagesContainer = document.getElementById('messages-container');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    </script>
</body>
</html>

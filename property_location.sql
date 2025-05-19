-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 19 mai 2025 à 20:43
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `property_location`
--

-- --------------------------------------------------------

--
-- Structure de la table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int(11) NOT NULL,
  `nights` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `host_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `bookings`
--

INSERT INTO `bookings` (`id`, `property_id`, `user_id`, `check_in`, `check_out`, `guests`, `nights`, `total_price`, `status`, `created_at`, `updated_at`, `host_id`) VALUES
(1, 1, 1, '2025-04-01', '2025-04-18', 10, 18, 213330.00, 'completed', '2025-05-08 18:19:15', '2025-05-08 21:12:01', 2),
(5, 2, 1, '2025-05-31', '2025-06-19', 1, 19, 351500.00, 'cancelled', '2025-05-17 07:35:09', '2025-05-17 07:36:40', 0),
(6, 15, 1, '2025-05-18', '2025-05-22', 1, 4, 48000.00, 'cancelled', '2025-05-18 16:04:31', '2025-05-19 18:18:01', 0),
(7, 15, 1, '2025-05-18', '2025-05-29', 3, 11, 132000.00, 'confirmed', '2025-05-18 17:17:46', '2025-05-19 18:11:49', 0),
(8, 15, 1, '2025-05-18', '2025-05-28', 1, 10, 120000.00, 'cancelled', '2025-05-18 17:21:31', '2025-05-18 17:21:47', 0),
(9, 16, 1, '2025-05-19', '2025-05-20', 1, 1, 2500.00, 'cancelled', '2025-05-19 20:16:58', '2025-05-19 20:17:39', 0);

-- --------------------------------------------------------

--
-- Structure de la table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `last_message_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

CREATE TABLE `favoris` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `favoris`
--

INSERT INTO `favoris` (`id`, `user_id`, `property_id`, `created_at`) VALUES
(51, 1, 2, '2025-05-19 20:14:05'),
(53, 1, 15, '2025-05-19 20:28:13');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data`, `is_read`, `created_at`) VALUES
(7, 2, 'booking', 'Réservation annulée', 'Une réservation pour belle maison a été annulée par le client.', '{\"booking_id\":1}', 0, '2025-05-08 21:09:50'),
(8, 2, 'booking', 'Réservation annulée', 'Une réservation pour belle maison a été annulée par le client.', '{\"booking_id\":1}', 0, '2025-05-08 21:12:01'),
(9, 2, 'new_booking', 'Nouvelle réservation', 'Vous avez reçu une nouvelle demande de réservation.', '{\"booking_id\":2,\"property_id\":2}', 0, '2025-05-17 07:23:32'),
(10, 2, 'booking', 'Nouvelle demande de réservation', 'Vous avez reçu une nouvelle demande de réservation.', '{\"booking_id\":5}', 0, '2025-05-17 07:35:09'),
(11, 2, 'booking', 'Réservation annulée', 'Une réservation pour Magnifique maison a été annulée par le client.', '{\"booking_id\":4}', 0, '2025-05-17 07:36:18'),
(12, 2, 'booking', 'Réservation annulée', 'Une réservation pour Magnifique maison a été annulée par le client.', '{\"booking_id\":3}', 0, '2025-05-17 07:36:32'),
(13, 2, 'booking', 'Réservation annulée', 'Une réservation pour Magnifique maison a été annulée par le client.', '{\"booking_id\":5}', 0, '2025-05-17 07:36:40'),
(14, 2, 'booking', 'Nouvelle demande de réservation', 'Vous avez reçu une nouvelle demande de réservation.', '{\"booking_id\":6}', 0, '2025-05-18 16:04:31'),
(15, 2, 'booking', 'Nouvelle demande de réservation', 'Vous avez reçu une nouvelle demande de réservation.', '{\"booking_id\":7}', 0, '2025-05-18 17:17:46'),
(16, 2, 'booking', 'Nouvelle demande de réservation', 'Vous avez reçu une nouvelle demande de réservation.', '{\"booking_id\":8}', 0, '2025-05-18 17:21:31'),
(17, 2, 'booking', 'Réservation annulée', 'Une réservation pour Sheraton a été annulée par le client.', '{\"booking_id\":8}', 0, '2025-05-18 17:21:47'),
(18, 1, 'booking', 'Réservation confirmée', 'Votre réservation pour Sheraton a été confirmée.', '{\"booking_id\":7}', 0, '2025-05-19 18:11:49'),
(19, 1, 'booking', 'Réservation annulée', 'Votre réservation pour Sheraton a été annulée par l\'hôte.', '{\"booking_id\":6}', 0, '2025-05-19 18:18:01'),
(20, 2, 'booking', 'Nouvelle demande de réservation', 'Vous avez reçu une nouvelle demande de réservation.', '{\"booking_id\":9}', 0, '2025-05-19 20:16:58'),
(21, 2, 'booking', 'Réservation annulée', 'Une réservation pour The best property ever a été annulée par le client.', '{\"booking_id\":9}', 0, '2025-05-19 20:17:39');

-- --------------------------------------------------------

--
-- Structure de la table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `address` varchar(50) NOT NULL,
  `housing_type` varchar(50) NOT NULL,
  `area` varchar(50) NOT NULL,
  `number_of_rooms` int(50) NOT NULL,
  `number_of_people` int(50) NOT NULL,
  `amenities` text NOT NULL,
  `other_amenities` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `photos` varchar(255) NOT NULL,
  `identity_document` varchar(255) NOT NULL,
  `property_deed` varchar(255) NOT NULL,
  `validated` tinyint(1) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `properties`
--

INSERT INTO `properties` (`id`, `title`, `address`, `housing_type`, `area`, `number_of_rooms`, `number_of_people`, `amenities`, `other_amenities`, `price`, `start_date`, `end_date`, `photos`, `identity_document`, `property_deed`, `validated`, `rating`, `review_count`, `user_id`, `created_at`, `description`) VALUES
(1, 'belle maison', 'oran point du jour', 'maison', '150', 6, 12, 'wifi,television', 'piscine', 21333, '2025-04-01', '2025-04-18', '../images/propertie1.jpg', 'uploads/docs/photo_2025-04-27_19-59-08.jpg', 'uploads/docs/photo_2025-04-27_19-59-11.jpg', 1, 1.00, 1, 2, '2025-05-08 21:17:28', 'Découvrez cet appartement moderne de 2 chambres situé au cœur de la ville, à quelques pas des meilleures boutiques, restaurants et transports en commun. Cet espace lumineux et aéré dispose d\'un salon ouvert avec de grandes baies vitrées, offrant une vue panoramique sur la ville. La cuisine est équipée d\'appareils haut de gamme, d\'un îlot central et de nombreux rangements. Les chambres sont spacieuses et bénéficient de placards intégrés. La salle de bain est élégante, avec une douche à l\'italienne et des finitions contemporaines. Profitez également d\'un balcon privé pour vos moments de détente. Cet appartement est parfait pour les professionnels ou les couples cherchant à vivre dans un cadre dynamique et pratique.\r\n'),
(2, 'Magnifique maison', 'Centre Ville, 31000, Oran', 'appartement', '75', 3, 4, 'climatisation,Sèche-linge', 'television', 18500, '2025-05-31', '2025-07-31', '../images/maison2.jpg', 'uploads/photos/photo_2025-04-27_19-59-05.jpg', 'uploads/photos/photo_2025-04-27_19-59-05.jpg', 1, 0.00, 0, 2, '2025-05-11 20:55:39', 'Cette magnifique maison de 3 chambres est située dans un quartier calme et résidentiel. Elle dispose d\'un grand jardin, idéal pour les familles et les animaux de compagnie. La cuisine moderne est entièrement équipée avec des appareils électroménagers en acier inoxydable et un plan de travail en granit. Le salon spacieux est baigné de lumière naturelle grâce à de grandes fenêtres, offrant une vue imprenable sur le jardin. À proximité, vous trouverez des écoles, des parcs et des commerces, rendant cette maison parfaite pour une vie de famille confortable. Ne manquez pas cette opportunité de vivre dans un cadre paisible tout en étant proche des commodités.\r\n'),
(15, 'Sheraton', '64 Route Des Falaises, Oran 31', 'appartement', '142', 3, 3, 'Hello, tests', 'wouaw, incroyable', 12000, '2025-05-08', '2025-05-31', 'https://cache.marriott.com/content/dam/marriott-renditions/ORNFP/ornfp-exterior-3064-hor-wide.jpg?output-quality=70&interpolation=progressive-bilinear&downsize=1336px:*', 'https://cache.marriott.com/content/dam/marriott-renditions/ORNFP/ornfp-exterior-3064-hor-wide.jpg?output-quality=70&interpolation=progressive-bilinear&downsize=1336px:*', 'https://cache.marriott.com/content/dam/marriott-renditions/ORNFP/ornfp-exterior-3064-hor-wide.jpg?output-quality=70&interpolation=progressive-bilinear&downsize=1336px:*', 1, 0.00, 0, 2, '2025-05-18 15:22:33', 'Évadez-vous vers la paisible côte méditerranéenne au Four Points by Sheraton Oran. Situé dans la ville portuaire historique d\'Oran, notre hôtel de luxe propose des chambres modernes, des restaurants de qualité, une piscine rooftop ainsi que des installations et services haut de gamme. Idéalement situé à proximité des plages locales, notre hôtel offre également un accès facile aux sites touristiques...'),
(16, 'The best property ever', '29 Avenue du Val de Montferrand, New York City', 'appartement', '64', 5, 5, 'WiFi,TV,Cuisine équipée,Machine à laver,Climatisation', 'piscine', 2500, '2025-05-19', '2025-08-25', '273818788.jpg', '', '', 1, 0.00, 0, 2, '2025-05-19 19:39:52', 'This will be your best experience');

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reviews`
--

INSERT INTO `reviews` (`id`, `property_id`, `user_id`, `booking_id`, `rating`, `comment`, `created_at`) VALUES
(2, 1, 1, 1, 1, 'It was the best accomodation I ever booked, Thank you this adventure', '2025-05-18 16:14:53');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('guest','host','admin') DEFAULT 'guest',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `phone`, `user_type`, `profile_image`, `created_at`, `last_login`) VALUES
(1, 'Bibo', 'hakemi@gmail.com', '$2y$10$mZ2g8pj8hTiCRk0F2xo0w.r0ZYOugZ6qKTAGJu6acE5BXV719hDPe', '0766736194', 'guest', '../uploads/profiles/profile_682a0e0ad7e4c4.71892899.jpg', '2025-05-08 16:19:46', '2025-05-19 20:27:37'),
(2, 'Thomas shelby', 'thomas@gmail.com', '$2y$10$F4Fu6ZOXuDRw3Vmkct1jkOr/MjiiH6SZLZ.3X/pHbq27OpOIYEpNW', '0766439165', 'host', 'https://img.freepik.com/psd-gratuit/illustration-3d-personne-lunettes-soleil_23-2149436188.jpg?semt=ais_hybrid&w=740', '2025-05-08 19:42:36', '2025-05-19 20:18:45'),
(3, 'tata', 'tata@gmail.com', '$2y$10$eFTLu2OSvqstlXNoGHPpe.TfPP9hTNfXC.XT.UGe98zwTMC0YL4tm', '0766589412', 'guest', '../uploads/profiles/profile_682a159579daf8.20105930.jpg', '2025-05-18 19:14:05', '2025-05-18 19:14:49'),
(7, 'hote', 'hote@gmail.com', '$2y$10$pJpFndxdcAJibjgVw2/TKekMX8XnsYTSTEkf47x4B/A1u1uNzm5HS', '0766139425', 'host', NULL, '2025-05-18 19:25:31', '2025-05-18 19:25:56');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user1_id` (`user1_id`),
  ADD KEY `user2_id` (`user2_id`);

--
-- Index pour la table `favoris`
--
ALTER TABLE `favoris`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_property` (`user_id`,`property_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `favoris`
--
ALTER TABLE `favoris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_4` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

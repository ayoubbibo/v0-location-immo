-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('guest', 'host', 'admin') DEFAULT 'guest',
    profile_image VARCHAR(255),
    created_at DATETIME NOT NULL,
    last_login DATETIME
);

-- Bookings Table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    user_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT NOT NULL,
    nights INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    FOREIGN KEY (property_id) REFERENCES annonce(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Reviews Table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (property_id) REFERENCES annonce(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Conversations Table
CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_id INT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user1_id) REFERENCES users(id),
    FOREIGN KEY (user2_id) REFERENCES users(id)
);

-- Messages Table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    booking_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Notifications Table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add columns to annonce table
ALTER TABLE annonce
ADD COLUMN rating DECIMAL(3, 2) DEFAULT 0,
ADD COLUMN review_count INT DEFAULT 0;

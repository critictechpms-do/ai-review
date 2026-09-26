-- Keep existing users table unchanged
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    otp VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Review Cards (replaces restaurants)
CREATE TABLE review_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    membership VARCHAR(50) DEFAULT 'trial',
    expiry_date DATETIME DEFAULT NULL,
    details JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug)
);

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    customer_name VARCHAR(255) DEFAULT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    body TEXT DEFAULT NULL,
    details JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_card_id (card_id),
    INDEX idx_rating (rating),
    FOREIGN KEY (card_id) REFERENCES review_cards(id) ON DELETE CASCADE
);

-- Low Rating Feedback
CREATE TABLE feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 3),
    status ENUM('new', 'read', 'resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_card_id (card_id),
    INDEX idx_status (status),
    FOREIGN KEY (card_id) REFERENCES review_cards(id) ON DELETE CASCADE
);

-- Keep platform_settings unchanged
CREATE TABLE platform_settings (
    whatsapp VARCHAR(20) NOT NULL PRIMARY KEY,
    details JSON DEFAULT NULL
);

-- Keep plans unchanged
CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    features JSON,
    cta VARCHAR(255),
    duration INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
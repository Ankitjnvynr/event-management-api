<?php

require_once __DIR__.'/../src/config.php';
$db = new Config();

$createUserTableSql = 'CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    google_id VARCHAR(50) DEFAULT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) DEFAULT NULL,
    name VARCHAR(50) NOT NULL,
    role ENUM("admin", "user") DEFAULT "user",
    avatar VARCHAR(255) DEFAULT NULL,
    points INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)';

$db->createTable($createUserTableSql);

$createUserDetailsTableSql = 'CREATE TABLE IF NOT EXISTS user_details (
    user_id INT NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    district VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    dob DATE DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)';

$db->createTable($createUserDetailsTableSql);

$createSessionsTableSql = 'CREATE TABLE IF NOT EXISTS `sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `refresh_token` TEXT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `ip_address` VARCHAR(255),
  `user_agent` TEXT,
  `device_info` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)';

$db->createTable($createSessionsTableSql);

$ceateActivityLogTableSql = 'CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    points_earned INT DEFAULT 0,
    points_spent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)';

$db->createTable($ceateActivityLogTableSql);



$createEventsTableSql = 'CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    is_all_day BOOLEAN DEFAULT FALSE,
    location VARCHAR(255) DEFAULT NULL,
    color VARCHAR(20) DEFAULT NULL,
    organizer_name VARCHAR(255) DEFAULT NULL,
    contact_phone VARCHAR(20) DEFAULT NULL,
    contact_email VARCHAR(100) DEFAULT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    registration_link VARCHAR(255) DEFAULT NULL,
    external_links TEXT DEFAULT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)';

$db->createTable($createEventsTableSql);

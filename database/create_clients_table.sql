CREATE DATABASE IF NOT EXISTS digital_traffic_tracker;
USE digital_traffic_tracker;

CREATE TABLE IF NOT EXISTS clients
(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    api_token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
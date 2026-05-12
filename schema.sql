CREATE DATABASE bulk_email_portal;
USE bulk_email_portal;

CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    company VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    subject VARCHAR(255),
    body LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    template_id INT,
    status ENUM('pending','processing','completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE campaign_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    contact_id INT,
    email VARCHAR(255),
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    sent_at DATETIME NULL
);

CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    email VARCHAR(255),
    status VARCHAR(50),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
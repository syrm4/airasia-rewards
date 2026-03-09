-- Database Setup
DROP DATABASE IF EXISTS rewards;
CREATE DATABASE rewards;
USE rewards;

-- USER Table
CREATE TABLE USER (
    userId    INT AUTO_INCREMENT PRIMARY KEY,
    userName  VARCHAR(100) NOT NULL UNIQUE,
    password  VARCHAR(255) NOT NULL,
    firstName VARCHAR(100) NOT NULL,
    lastName  VARCHAR(100) NOT NULL,
    role      ENUM('Admin', 'Customer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ACCOUNT Table (Point Balances)
CREATE TABLE ACCOUNT (
    accountId   INT AUTO_INCREMENT PRIMARY KEY,
    userId      INT NOT NULL,
    accountType VARCHAR(100) NOT NULL,
    points      INT NOT NULL,
    FOREIGN KEY (userId) REFERENCES USER(userId) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- GIFTCARD Table (Inventory)
CREATE TABLE GIFTCARD (
    cardId    INT AUTO_INCREMENT PRIMARY KEY,
    cardName  VARCHAR(100) NOT NULL,
    cardType  VARCHAR(100) NOT NULL,
    cardValue DECIMAL(10,2) NOT NULL,
    points    INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- REDEMPTION Table
CREATE TABLE REDEMPTION (
    redeemId       INT(11) AUTO_INCREMENT PRIMARY KEY,
    date           VARCHAR(100) NOT NULL,
    pointsRedeemed INT(50) NOT NULL,
    accountId      INT(11) NOT NULL,
    cardId         INT(11) NOT NULL,
    FOREIGN KEY (accountId) REFERENCES ACCOUNT(accountId) ON DELETE CASCADE,
    FOREIGN KEY (cardId)    REFERENCES GIFTCARD(cardId)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- bsmith    / mysecret  (Admin)
-- pjones    / acrobat   (Customer)
-- asmith    / pass123   (Customer)
-- bwilliams / pass123   (Customer)
-- jmilner   / pass123   (Customer)
INSERT INTO USER (userName, password, firstName, lastName, role) VALUES
('bsmith',    '$2y$10$RDOg/kZW/8xKzoTuqUNrB.HKH.w9V.G9Xj6ndtH1fHE2LNzTBH66u', 'Bob',   'Smith',    'Admin'),
('pjones',    '$2y$10$HqPjAPrdBpQ53cRDgjLXUOc7FQCMNhM7d1Jm3vX/5fLV8zP3ZCIGy', 'Pam',   'Jones',    'Customer'),
('asmith',    '$2y$10$H4ljXfMPIsAcrxiyflo0iuPoomnJ2h7kk7LzkzBdl1RP2OIkk5s.W', 'Alice', 'Smith',    'Customer'),
('bwilliams', '$2y$10$Sjp9Vu1.baoqtIcRLQ.5zeoxrsfCl8T.tTyQRh9qz8myXvU5CwI06', 'Bob',   'Williams', 'Customer'),
('jmilner',   '$2y$10$IRDxUrT6c9MM6AwgBrnJz.YkWD7jGUEFosgfj8T7rPRzITK3pbg/O', 'Joe',   'Milner',   'Customer');

-- INITIAL BALANCES
INSERT INTO ACCOUNT (userId, accountType, points) VALUES
(1, 'Corporate', 0),
(2, 'Gold',      15000),
(3, 'Silver',    5000),
(4, 'Gold',      10000),
(5, 'Silver',    7500);

-- REWARDS (20 TOTAL)
INSERT INTO GIFTCARD (cardName, cardType, cardValue, points) VALUES
('Flight Voucher $50',    'Travel',    50.00,  5000),
('Flight Voucher $100',   'Travel',   100.00, 10000),
('Flight Voucher $250',   'Travel',   250.00, 25000),
('Hotel Credit $50',      'Travel',    50.00,  5000),
('Hotel Credit $100',     'Travel',   100.00, 10000),
('Car Rental $25',        'Travel',    25.00,  2500),
('Car Rental $75',        'Travel',    75.00,  7500),
('Airport Lounge Pass',   'Service',   40.00,  4000),
('Priority Boarding',     'Service',   15.00,  1500),
('Extra Baggage 15kg',    'Service',   30.00,  3000),
('Dining Voucher $10',    'Food',      10.00,  1000),
('Dining Voucher $25',    'Food',      25.00,  2500),
('Dining Voucher $50',    'Food',      50.00,  5000),
('Retail Gift Card $20',  'Shopping',  20.00,  2000),
('Retail Gift Card $50',  'Shopping',  50.00,  5000),
('Retail Gift Card $100', 'Shopping', 100.00, 10000),
('Entertainment Pass',    'Lifestyle', 35.00,  3500),
('Digital Media Sub',     'Lifestyle', 15.00,  1500),
('Gas Card $25',          'Travel',    25.00,  2500),
('Travel Accessory Kit',  'Lifestyle', 20.00,  2000);

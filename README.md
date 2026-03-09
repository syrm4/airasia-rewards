# AirAsia Mileage Rewards Redemption System

A PHP/MySQL web application that allows Air Asia customers to redeem gift cards using their reward points. Built as part of a Web Applications course project.

## Overview

This system provides two types of users (Admin and Customer) with role-based access to manage and redeem a catalog of 20 mileage reward gift cards.

## Features

- **Authentication** — Secure login with bcrypt password hashing and `password_verify()`
- **Session Management** — All pages are gated behind login; users are redirected if not authenticated
- **Role-Based Authorization** — Admin and Customer roles with separate capabilities
- **Gift Card Inventory** — Browse, view, add, update, and delete gift cards
- **Customer Management** — Admins can enroll new customers with hashed passwords
- **Rewards Redemption** — Customers can redeem gift cards using their points balance
- **Prepared Statements** — All database queries use MySQLi prepared statements to prevent SQL injection

## Tech Stack

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML, CSS
- **Local Server:** MAMP (Mac) / WAMP (Windows)

## Project Structure

```
airasiajm/
├── CSS/
│   └── style.css
├── images/
│   ├── logo.png
│   └── giftcard.png
├── auth.php          # Session gating and role functions
├── card-add.php      # Add a new gift card (Admin only)
├── card-delete.php   # Delete a gift card (Admin only)
├── card-details.php  # View details of one gift card
├── card-list.php     # View all gift cards
├── card-update.php   # Update a gift card (Admin only)
├── cust-add.php      # Add a new customer (Admin only)
├── db-config.php     # Database connection configuration
├── login.php         # Login page
├── logout.php        # Logout and session destroy
├── redeem.php        # Gift card redemption logic
└── setup.sql         # Database schema and seed data
```

## Database Schema

- **USER** — Stores user accounts with bcrypt hashed passwords and roles
- **ACCOUNT** — Stores point balances linked to each user
- **GIFTCARD** — Stores the gift card catalog (20 cards)
- **REDEMPTION** — Logs each redemption with date, points redeemed, and card details

## Setup Instructions

### 1. Install WAMP (Windows) or MAMP (Mac)

### 2. Copy project files
Place the `airasiajm` folder in your server's web root:
- **WAMP:** `C:\wamp64\www\`
- **MAMP:** `/Applications/MAMP/htdocs/`

### 3. Import the database
1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Click **Import**
3. Select `setup.sql` and click **Go**

### 4. Configure the database connection
Open `db-config.php` and verify the settings match your environment:

**WAMP:**
```php
$hn = 'localhost';
$pw = '';
```

**MAMP:**
```php
$hn = 'localhost:8889';
$pw = 'root';
```

### 5. Launch the app
- **WAMP:** `http://localhost/airasiajm/login.php`
- **MAMP:** `http://localhost:8888/airasiajm/login.php`

## Test Accounts

| Username | Password | Role |
|----------|----------|------|
| bsmith | mysecret | Admin |
| pjones | acrobat | Customer |
| asmith | pass123 | Customer |
| bwilliams | pass123 | Customer |
| jmilner | pass123 | Customer |

## User Roles

### Admin (e.g. bsmith)
- View gift card list and details
- Add, update, and delete gift cards
- Add new customers

### Customer (e.g. pjones)
- View gift card list and details
- Redeem gift cards using points balance

## Site Map

```
Login
  └── Card List
        ├── Card Add
        ├── Cust Add
        └── Card Details
              ├── Card Update
              └── Card Delete
```

## Security Features
- Passwords hashed with `PASSWORD_BCRYPT`
- All queries use MySQLi prepared statements
- Session-based authentication on every page
- XSS prevention with `htmlspecialchars()` on all output
- Role enforcement both visually and at the page level

## Author
syrm4

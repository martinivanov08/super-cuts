# SuperCuts — Barbershop Platform

A clean, dark-themed barbershop website with an online reservation system and an admin panel. Built with plain HTML, CSS, PHP and MySQL.

---

## Features

- **Landing page** — hero, services, barber team, gallery, booking form
- **Online reservations** — customers pick a service, barber, date (custom calendar) and time slot
- **Double-booking protection** — same barber/date/time can't be booked twice
- **Admin panel** — view all reservations, filter by status, search, confirm or cancel bookings
- **Fully in Bulgarian**

---

## Stack

| Layer    | Technology          |
|----------|---------------------|
| Frontend | HTML, CSS, JS       |
| Backend  | PHP 8+              |
| Database | MySQL (via XAMPP)   |
| Server   | Apache (via XAMPP)  |

---

## Project Structure

```
barbershop_platform/
├── index.php       ← Main site (landing page + booking form)
├── admin.php       ← Admin dashboard
├── reserve.php     ← Handles reservation form POST
├── config.php      ← Database connection settings
├── setup.sql       ← One-time DB + table creation script
└── css/
    └── style.css   ← All styles
```

---

## Setup

### 1. Requirements

- [XAMPP](https://www.apachefriends.org/) with **Apache** and **MySQL** running

### 2. Copy files

Place the `barbershop_platform` folder inside XAMPP's web root:

```
C:\xampp\htdocs\barbershop_platform\
```

### 3. Create the database

Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and run the contents of `setup.sql`, or use the MySQL CLI:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "source C:/xampp/htdocs/barbershop_platform/setup.sql"
```

### 4. Configure the connection

Edit `config.php` if your MySQL credentials differ from the defaults:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'barbershop');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 5. Open in browser

| Page        | URL                                              |
|-------------|--------------------------------------------------|
| Main site   | http://localhost/barbershop_platform/            |
| Admin panel | http://localhost/barbershop_platform/admin.php   |

---

## Admin Credentials

| Field    | Value      |
|----------|------------|
| Username | `admin`    |
| Password | `blade2024`|

To change them, edit the constants at the top of `admin.php`:

```php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'blade2024');
```

---

## Services & Barbers

Defined in the frontend and validated server-side in `reserve.php`.

**Services:** Прическа, Оформяне на брада, Прическа + Брада, Бръснене с гореща кърпа, Детска прическа

**Barbers:** Marco, Diego, Luca

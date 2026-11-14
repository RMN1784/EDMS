<?php
/**
 * Emergency Medicine Delivery System (Online Pharmacy System)
 * Central configuration. Edit the values below to match your XAMPP / WAMP setup.
 */

// ---------- Database (MySQL / MariaDB) ----------
define('DB_HOST', getenv('EMDS_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('EMDS_DB_PORT') ?: '3306');
define('DB_USER', getenv('EMDS_DB_USER') ?: 'root');
define('DB_PASS', getenv('EMDS_DB_PASS') !== false ? getenv('EMDS_DB_PASS') : '');
define('DB_NAME', getenv('EMDS_DB_NAME') ?: 'pharmacy');

// ---------- Application ----------
define('APP_NAME', 'Emergency Medicine Delivery System');
define('APP_SHORT', 'EMDS');
define('APP_TZ', 'Asia/Kolkata');

// Promised delivery time in minutes. Set to 2 or 3 during the viva demo so the
// live tracking animation completes quickly. Use 20 for the real promise.
define('DELIVERY_MINUTES', 20);

// Delivery charges (INR)
define('DELIVERY_FEE', 30.00);          // flat emergency delivery fee
define('FREE_DELIVERY_ABOVE', 500.00);  // free delivery above this cart value

// Pharmacy (store) details shown on receipts
define('STORE_NAME', 'EMDS 24x7 Pharmacy');
define('STORE_ADDRESS', 'Nangloi, New Delhi - 110041');
define('STORE_PHONE', '1800-000-0020');

// Prescription upload rules
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_UPLOAD_EXT', ['jpg', 'jpeg', 'png', 'pdf']);

// Delivery agents available for assignment (name => phone)
$DELIVERY_AGENTS = [
    'Rahul Sharma' => '9810011001',
    'Amit Verma'   => '9810011002',
    'Sunil Yadav'  => '9810011003',
    'Deepak Singh' => '9810011004',
    'Imran Khan'   => '9810011005',
];

// Optional: force a base URL path if auto-detection fails (e.g. '/emds'). Leave '' for auto.
define('BASE_PATH_OVERRIDE', '');

date_default_timezone_set(APP_TZ);

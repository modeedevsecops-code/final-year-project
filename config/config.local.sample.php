<?php
// Copy this file to config/config.local.php and edit for YOUR machine.
// config.local.php is gitignored, so your local credentials never get committed.
//
// Find the exact values in Local: open your site → the "Database" tab shows
// Host, Port, Username, Password, Database (or open Adminer from there).

// ---- Local by Flywheel — Windows (reach MySQL over TCP + the site's port) ----
define('DB_HOST', '127.0.0.1');   // from the site's Database tab (often 127.0.0.1)
define('DB_PORT', 10005);         // <-- REPLACE with the port shown in that tab
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'donor_app');

// ---- Local by Flywheel — macOS (socket also works; port optional) ----
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', 'root');
// define('DB_NAME', 'donor_app');

// ---- XAMPP / MAMP ----
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');          // empty
// define('DB_NAME', 'donor_app');

// Optional: enable on-screen errors for local dev only.
// define('APP_DEBUG', true);

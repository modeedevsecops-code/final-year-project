<?php
// Copy this file to config/config.local.php and edit for your machine.
// config.local.php is gitignored so your local credentials never get committed.

// --- Local by Flywheel (per-site MySQL is root / "root") ---
define('DB_HOST', 'localhost'); // Local's PHP points "localhost" at the site's mysqld socket automatically
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'donor_app');

// --- XAMPP / MAMP would instead be: ---
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');      // empty
// define('DB_NAME', 'donor_app');

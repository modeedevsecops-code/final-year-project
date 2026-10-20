<?php

// Optional machine-specific overrides (gitignored). The app runs under Local by
// Flywheel (MySQL user root / password "root"); the defaults below match it.
// Copy config/config.local.sample.php to config/config.local.php to override the
// DB_* constants for your machine (e.g. the TCP port Local uses on Windows).
// MUST load before functions.php, which instantiates dbconfig on include.
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

require_once('config/functions.php');

class dbconfig
{

    public $connection;

    public function __construct()
    {
        $this->db_connect();
    }

    public function db_connect()
    {
        // Defaults match Local by Flywheel on macOS (socket via localhost).
        // config.local.php can override any of these. DB_PORT / DB_SOCKET are
        // optional — needed on Windows "Local", where MySQL is reached over TCP
        // on 127.0.0.1 with a per-site port (see the site's Database tab).
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : 'root';
        $name = defined('DB_NAME') ? DB_NAME : 'donor_app';
        $port = defined('DB_PORT') && DB_PORT ? (int) DB_PORT : null;
        $sock = defined('DB_SOCKET') && DB_SOCKET ? DB_SOCKET : null;

        $this->connection = mysqli_connect($host, $user, $pass, $name, $port, $sock);

        if (mysqli_connect_error()) {
            die("Connection Failed");
        }

        // Match the schema's utf8mb4 so multibyte characters (e.g. the en-dash
        // in "Officer – Hospital") don't come back as mojibake.
        mysqli_set_charset($this->connection, 'utf8mb4');

        // PHP and MySQL must agree on the clock, or every deadline countdown and
        // "x min ago" is off by the difference (PHP defaults to UTC, MySQL to the
        // machine's zone). Both run on Nigerian time; config.local.php can set
        // BL_TIMEZONE to override.
        date_default_timezone_set(defined('BL_TIMEZONE') ? BL_TIMEZONE : 'Africa/Lagos');
        mysqli_query($this->connection, "SET time_zone = '" . date('P') . "'");
    }

    public function check($a)
    {
        $check = mysqli_real_escape_string($this->connection, $a);
        return $check;
    }

}

?>
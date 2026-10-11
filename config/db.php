<?php

// Optional machine-specific overrides (gitignored). Lets this same code run
// under XAMPP/MAMP (root / empty password) and under Local by Flywheel
// (root / "root") without editing this file. Copy config/config.local.sample.php
// to config/config.local.php and set the four DB_* constants for your machine.
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
        // Defaults match the XAMPP/MAMP assumption the app shipped with.
        // config.local.php can override any of these. DB_PORT / DB_SOCKET are
        // optional — needed on Windows "Local", where MySQL is reached over TCP
        // on 127.0.0.1 with a per-site port (see the site's Database tab).
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
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
    }

    public function check($a)
    {
        $check = mysqli_real_escape_string($this->connection, $a);
        return $check;
    }

}

?>
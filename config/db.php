<?php

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
        $this->connection = mysqli_connect("localhost", "root", "", "donor_app");

        if (mysqli_connect_error()) {
            die("Connection Failed");
        }
    }

    public function check($a)
    {
        $check = mysqli_real_escape_string($this->connection, $a);
        return $check;
    }

}

?>
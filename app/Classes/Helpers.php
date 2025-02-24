<?php namespace App\Classes;

use mysqli;

class Helpers
{
    /*
     * Get Tracker default database connection
     */
    public static function getDbConnection()
    {
        $db = new mysqli('localhost', 'root', '', 'digital_traffic_tracker');

        if ($db->connect_error) {
            die("Database connection failed: " . $db->connect_error);
        }

        return $db;
    }

    // Initialize CSRF Token used in form protection
    public static function initCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
        }
    }
}
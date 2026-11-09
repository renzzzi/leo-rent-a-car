<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * This is where all database interactions are handled. It provides a single point of connection to 
 * the database and can be extended to include common database operations if needed.
 */

class Database {
    private static ?PDO $connection = null;

    public static function connect(): PDO {
        if (self::$connection === null) {

            $config = require __DIR__ . '/../../config/database.php';

            $port = !empty($config['port']) ? ";port={$config['port']}" : '';
            $dsn = "mysql:host={$config['host']}{$port};dbname={$config['dbname']};charset={$config['charset']}";

            try {
                self::$connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$connection;
    }
}
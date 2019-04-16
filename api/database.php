<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

class Database
{
    private static $dsn = 'mysql:host='. DB_HOST .';dbname='. DB_NAME .';charset='. DB_CHARSET;
    private static $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    public static function getConnection() {
        return new PDO(self::$dsn, DB_USER, DB_PASS, self::$options);
    }

}
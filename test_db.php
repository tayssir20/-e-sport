<?php
require 'vendor/autoload.php';

$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => '127.0.0.1',
    'dbname' => 'esport-db',
    'user' => 'root',
    'password' => ''
]);

$result = $connection->executeQuery('SELECT email FROM user LIMIT 1');
$email = $result->fetchOne();

<?php
require 'vendor/autoload.php';

$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => '127.0.0.1',
    'dbname' => 'esport-db',
    'user' => 'root',
    'password' => ''
]);

$sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'esport-db' AND TABLE_NAME = 'user'";
$stmt = $connection->executeQuery($sql);
$columns = $stmt->fetchAllAssociative();

echo "Columns in user table:\n";
foreach ($columns as $column) {
    echo $column['COLUMN_NAME'] . " - " . $column['DATA_TYPE'] . "\n";
}

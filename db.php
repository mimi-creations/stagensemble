<?php
$host     = getenv('MYSQLHOST')     ?: getenv('DB_HOST');
$dbname   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME');
$username = getenv('MYSQLUSER')     ?: getenv('DB_USER');
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS');
$port     = getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: 3306;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]  
    );
    $pdo -> exec("SET SESSION wait_timeout=600");
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

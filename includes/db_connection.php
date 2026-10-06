<?php
$db_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

if ($db_url) {
    $parsed_url = parse_url($db_url);
    $host = $parsed_url['host'] ?? 'localhost';
    $port = $parsed_url['port'] ?? '3306';
    $user = $parsed_url['user'] ?? 'root';
    $pass = $parsed_url['pass'] ?? '';
    $db   = isset($parsed_url['path']) ? ltrim($parsed_url['path'], '/') : 'barbearia';
} else {
    $host = getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: 'localhost');
    $db   = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: 'barbearia');
    $user = getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: 'root');
    $pass = getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: '');
    $port = getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: '3306');
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Em produção, não exibiria o erro detalhado
     // throw new \PDOException($e->getMessage(), (int)$e->getCode());
     die("Erro ao conectar com o banco de dados ($db): " . $e->getMessage() . " | Host=$host | Port=$port | User=$user");
}
?>

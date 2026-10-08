<?php
require_once 'includes/db_connection.php';
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE barbeiro;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "Barbeiros zerados!";
?>

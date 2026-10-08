<?php
require_once "includes/db_connection.php";
$stmt = $pdo->query("DESCRIBE servico");
print_r($stmt->fetchAll());
?>

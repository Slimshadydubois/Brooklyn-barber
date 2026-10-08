<?php
require_once "includes/db_connection.php";
$stmt = $pdo->query("SELECT * FROM barbeiro");
$barbeiros = $stmt->fetchAll();
print_r($barbeiros);
?>

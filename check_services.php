<?php
require_once "includes/db_connection.php";
$stmt = $pdo->query("SELECT * FROM servico");
$servicos = $stmt->fetchAll();
print_r($servicos);
?>

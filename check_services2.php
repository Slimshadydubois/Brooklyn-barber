<?php
require_once "includes/db_connection.php";
$stmt = $pdo->query("SELECT id, nome, valor, id_barbeiro FROM servico");
$servicos = $stmt->fetchAll();
var_dump($servicos);
?>

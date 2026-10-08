<?php
require_once "includes/db_connection.php";
$stmt = $pdo->query("SELECT id, nome, id_barbeiro FROM servico");
$servicos = $stmt->fetchAll();
var_dump($servicos);
?>

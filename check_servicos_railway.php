<?php
require_once 'includes/db_connection.php';
$stmt = $pdo->query("SELECT * FROM servico");
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($servicos);
?>

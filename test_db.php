<?php
require 'includes/db_connection.php';
$stmt = $pdo->query('SELECT COUNT(*) FROM agendamento WHERE id_barbeiro=2');
echo "Count: " . $stmt->fetchColumn() . "\n";
$stmt2 = $pdo->query('SELECT COUNT(*) FROM cliente WHERE id=1');
echo "Cliente 1 count: " . $stmt2->fetchColumn() . "\n";
$stmt3 = $pdo->query('SELECT COUNT(*) FROM servico WHERE id_barbeiro=2');
echo "Servicos count: " . $stmt3->fetchColumn() . "\n";

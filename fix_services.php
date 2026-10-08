<?php
require_once 'includes/db_connection.php';
$pdo->exec("UPDATE servico SET valor = 15 WHERE nome = 'Acabamento'");
$pdo->exec("UPDATE servico SET valor = 15 WHERE nome LIKE 'Pigmenta%'");
$pdo->exec("UPDATE servico SET valor = 150 WHERE nome LIKE 'Qu%mica ap.'");
echo "Updated!";
?>

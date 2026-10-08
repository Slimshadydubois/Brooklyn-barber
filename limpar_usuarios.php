<?php
require_once 'includes/db_connection.php';
try {
    // Note: If there are foreign keys, we might need to disable them temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE usuario;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Tabela usuario zerada com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>

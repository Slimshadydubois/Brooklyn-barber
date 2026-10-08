<?php
require_once 'includes/db_connection.php';

$servicos = [
    ['Navalhado', 40],
    ['Corte', 35],
    ['Corte 1 pente', 15],
    ['Barba', 25],
    ['Corte e barba', 55],
    ['Navalhado e barba', 60],
    ['Sobrancelha', 10],
    ['Acabamento', 15],
    ['Pigmentação', 15],
    ['Química ap.', 150]
];

try {
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servico WHERE nome = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO servico (nome, descricao, valor, duracao) VALUES (?, '', ?, 30)");
    $stmtUpdate = $pdo->prepare("UPDATE servico SET valor = ? WHERE nome = ?");
    
    foreach ($servicos as $s) {
        $nome = $s[0];
        $valor = $s[1];
        
        $stmtCheck->execute([$nome]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsert->execute([$nome, $valor]);
        } else {
            $stmtUpdate->execute([$valor, $nome]);
        }
    }
    
    // Fallback for special characters just in case
    $pdo->exec("UPDATE servico SET valor = 15 WHERE nome LIKE 'Pigmenta%'");
    $pdo->exec("UPDATE servico SET valor = 150 WHERE nome LIKE 'Qu%mica ap.'");

    echo "Servicos sincronizados com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>

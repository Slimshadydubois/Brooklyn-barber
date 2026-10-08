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
    // 1. Inserir para a tabela servico sem barbeiro (serviços gerais)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servico WHERE nome = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO servico (nome, descricao, valor, duracao) VALUES (?, '', ?, 30)");
    
    foreach ($servicos as $s) {
        $nome = $s[0];
        $valor = $s[1];
        
        $stmtCheck->execute([$nome]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsert->execute([$nome, $valor]);
        }
    }

    // 2. Inserir para cada barbeiro se a coluna id_barbeiro existir na tabela servico
    // (Removido para não duplicar os serviços que já são gerais para todos)


    echo "Serviços adicionados com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

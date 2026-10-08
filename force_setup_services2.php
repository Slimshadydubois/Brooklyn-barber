<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'includes/db_connection.php';

try {
    // 1. Delete existing global services to avoid duplicates
    // Wait, do we need to alter table? Let's check if id_barbeiro exists.
    $stmt = $pdo->query("DESCRIBE servico");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('id_barbeiro', $columns)) {
        echo "Adicionando id_barbeiro na tabela servico...\n";
        $pdo->exec("ALTER TABLE servico ADD COLUMN id_barbeiro INT NULL DEFAULT NULL, ADD FOREIGN KEY (id_barbeiro) REFERENCES barbeiro(id) ON DELETE CASCADE");
    }

    $pdo->exec("DELETE FROM servico WHERE id_barbeiro IS NULL");

    // 2. Insert new default services
    $servicos = [
        ['nome' => 'Navalhado', 'descricao' => 'Corte navalhado', 'valor' => 40, 'duracao' => 45],
        ['nome' => 'Corte', 'descricao' => 'Corte padrão', 'valor' => 35, 'duracao' => 30],
        ['nome' => 'Corte 1 pente', 'descricao' => 'Corte utilizando apenas 1 pente', 'valor' => 15, 'duracao' => 20],
        ['nome' => 'Barba', 'descricao' => 'Serviço de barba', 'valor' => 25, 'duracao' => 30],
        ['nome' => 'Corte e barba', 'descricao' => 'Combo de corte e barba', 'valor' => 55, 'duracao' => 60],
        ['nome' => 'Navalhado e barba', 'descricao' => 'Combo de corte navalhado e barba', 'valor' => 60, 'duracao' => 75],
        ['nome' => 'Sobrancelha', 'descricao' => 'Serviço de sobrancelha', 'valor' => 10, 'duracao' => 15],
        ['nome' => 'Acabamento', 'descricao' => 'Acabamento', 'valor' => 15, 'duracao' => 15],
        ['nome' => 'Pigmentação', 'descricao' => 'Serviço de pigmentação', 'valor' => 15, 'duracao' => 30],
        ['nome' => 'Química ap.', 'descricao' => 'Aplicação de química', 'valor' => 150, 'duracao' => 90]
    ];

    $stmt = $pdo->prepare("INSERT INTO servico (nome, descricao, valor, duracao, id_barbeiro) VALUES (?, ?, ?, ?, NULL)");

    foreach ($servicos as $s) {
        $stmt->execute([$s['nome'], $s['descricao'], $s['valor'], $s['duracao']]);
    }

    echo "Servicos sincronizados com sucesso\n";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

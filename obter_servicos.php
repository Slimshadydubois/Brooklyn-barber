<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'includes/db_connection.php';

$id_barbeiro = isset($_GET['id_barbeiro']) ? intval($_GET['id_barbeiro']) : 0;

try {
    if ($id_barbeiro > 0) {
        // Busca os serviços específicos do barbeiro e os serviços gerais (id_barbeiro IS NULL)
        $stmt = $pdo->prepare("SELECT id, nome, descricao, valor, duracao FROM servico WHERE id_barbeiro = :id_barbeiro OR id_barbeiro IS NULL ORDER BY nome ASC");
        $stmt->execute(['id_barbeiro' => $id_barbeiro]);
        $servicos = $stmt->fetchAll();
    } else {
        // Se nenhum barbeiro for informado, retorna todos os serviços gerais
        $stmt = $pdo->query("SELECT id, nome, descricao, valor, duracao FROM servico WHERE id_barbeiro IS NULL ORDER BY nome ASC");
        $servicos = $stmt->fetchAll();
    }
    
    echo json_encode($servicos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}


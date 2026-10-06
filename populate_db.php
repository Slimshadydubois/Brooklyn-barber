<?php
require_once 'includes/db_connection.php';

echo "<h1>População do Banco de Dados</h1>";

try {
    // Roda as queries do seed.sql para adicionar os serviços e horários base
    $seed_sql = file_get_contents('db/seed.sql');
    if ($seed_sql) {
        $pdo->exec($seed_sql);
        echo "<p style='color: green;'>✅ Serviços padrão (Corte, Barba, etc) adicionados com sucesso!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ O arquivo db/seed.sql não foi encontrado.</p>";
    }
    
    echo "<h3>O que fazer agora:</h3>";
    echo "<ul>";
    echo "<li><a href='cadastro.php'>Crie sua primeira conta como Barbeiro</a></li>";
    echo "<li><a href='index.php'>Acesse a página inicial da Barbearia</a></li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro ao popular as tabelas: " . $e->getMessage() . "</p>";
}
?>

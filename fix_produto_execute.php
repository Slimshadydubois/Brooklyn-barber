<?php
$file = 'barbeiro_dashboard.php';
$content = file_get_contents($file);
$content = str_replace("$stmt->execute(['n' => $nome, 'd' => $descricao, 'p' => $preco, 'desc' => $desconto, 'f' => $foto_path, 'id' => $id_prod, 'id_b' => $barbeiro_id]);", "$stmt->execute(['n' => $nome, 'd' => $descricao, 'p' => $preco, 'desc' => $desconto, 'f' => $foto_path, 'id' => $id_prod]);", $content);
$content = str_replace("$stmt->execute(['id' => $id_prod, 'id_b' => $barbeiro_id]);", "$stmt->execute(['id' => $id_prod]);", $content);
file_put_contents($file, $content);
echo "Done";
?>

<?php
session_start();
$_SESSION['usuario_id']=2;
$_SESSION['usuario_perfil']=3;
$_SESSION['barbeiro_id']=2;
$_SESSION['usuario_nome']='cabeleiroteste';
ob_start();
include 'barbeiro_dashboard.php';
$output = ob_get_clean();

if (strpos($output, 'Nenhum agendamento encontrado') !== false) {
    echo 'Empty list found' . "\n";
} else {
    echo 'Has data in list' . "\n";
}

preg_match('/<div[^>]*>(\d+)<\/div>\s*<div[^>]*>Hoje<\/div>/s', $output, $m1);
echo "Hoje: " . ($m1[1] ?? 'Not found') . "\n";

preg_match('/<div[^>]*>(\d+)<\/div>\s*<div[^>]*>Nesta Semana<\/div>/s', $output, $m2);
echo "Semana: " . ($m2[1] ?? 'Not found') . "\n";

preg_match('/const movimentoDias = (\[.*?\]);/s', $output, $m3);
echo "movimentoDias: " . ($m3[1] ?? 'Not found') . "\n";

preg_match('/const movimentoHoras = (\[.*?\]);/s', $output, $m4);
echo "movimentoHoras: " . ($m4[1] ?? 'Not found') . "\n";

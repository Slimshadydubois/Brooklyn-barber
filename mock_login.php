<?php
session_start();
$_SESSION['usuario_id'] = 2;
$_SESSION['usuario_username'] = 'barbeiroteste';
$_SESSION['usuario_perfil'] = 3;
$_SESSION['barbeiro_id'] = 2;
$_SESSION['usuario_nome'] = 'cabeleiroteste';
header("Location: barbeiro_dashboard.php");
exit;

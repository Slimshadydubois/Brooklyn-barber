<?php
$_POST['username'] = 'barbeiroteste';
$_POST['senha'] = '123'; // Is it 123?
$_SERVER['REQUEST_METHOD'] = 'POST';
include 'login.php';
var_dump($_SESSION);

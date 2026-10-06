<?php
require_once 'includes/db_connection.php';
require_once 'vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setApplicationName("Barbearia Agendamento");
$client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);

// Define o caminho para as credenciais baixadas do Google Cloud
$credentialsPath = 'includes/credentials.json';
$tokenPath = 'includes/token.json';

if (!file_exists($credentialsPath)) {
    echo "<h3>Erro: credentials.json não encontrado</h3>";
    echo "<p>Você precisa baixar o arquivo JSON do 'ID do cliente OAuth 2.0' no Google Cloud Console, renomeá-lo para <b>credentials.json</b> e colocá-lo na pasta <b>includes/</b> antes de continuar.</p>";
    exit;
}

$client->setAuthConfig($credentialsPath);
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// Callback URL - para onde o Google redireciona após o login
$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
$client->setRedirectUri($redirect_uri);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (array_key_exists('error', $token)) {
        echo "Erro ao obter o token: " . htmlspecialchars($token['error']);
        exit;
    }
    
    $client->setAccessToken($token);

    // Salvar o token
    file_put_contents($tokenPath, json_encode($token));
    echo "<h3>Integração com Google Agenda concluída com sucesso!</h3>";
    echo "<p>O arquivo <b>token.json</b> foi gerado. Seus agendamentos agora serão lançados automaticamente na agenda da conta autenticada.</p>";
    echo "<a href='index.php'>Voltar ao sistema</a>";
    
} else {
    // Redireciona o usuário para o Google para autorizar o acesso à agenda
    $authUrl = $client->createAuthUrl();
    echo "<h3>Autorização do Google Agenda</h3>";
    echo "<p>Para conectar a barbearia ao seu Google Agenda, clique no botão abaixo, faça login com a conta da barbearia e conceda as permissões solicitadas.</p>";
    echo "<a href='" . filter_var($authUrl, FILTER_SANITIZE_URL) . "' style='display:inline-block; padding: 10px 15px; background-color: #4285F4; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;'>Autorizar Google Agenda</a>";
}

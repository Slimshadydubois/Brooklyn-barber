<?php
require_once __DIR__ . '/../vendor/autoload.php';

class GoogleCalendarAPI {
    private $client;
    
    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setApplicationName("Barbearia Agendamento");
        $this->client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        
        // Arquivos de credenciais e tokens do OAuth 2.0
        $credentialsPath = __DIR__ . '/credentials.json';
        $tokenPath = __DIR__ . '/token.json';
        
        if (file_exists($credentialsPath)) {
            $this->client->setAuthConfig($credentialsPath);
        }

        // Se já tivermos um token salvo do usuário, nós o carregamos
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }

        // Se o token estiver expirado, tentamos renová-lo usando o refresh token
        if ($this->client->isAccessTokenExpired() && $this->client->getRefreshToken()) {
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
        }
    }

    public function criarEvento($titulo, $descricao, $inicioDatetime, $fimDatetime, $emailCliente = null) {
        $tokenPath = __DIR__ . '/token.json';
        
        if (!file_exists($tokenPath) || $this->client->isAccessTokenExpired()) {
            error_log("Aviso: Autenticação do Google Calendar (token.json) não está configurada ou expirou.");
            return false;
        }

        try {
            $service = new Google_Service_Calendar($this->client);
            
            $eventData = [
                'summary' => $titulo,
                'description' => $descricao,
                'start' => [
                    'dateTime' => $inicioDatetime, // Ex: 2024-05-28T09:00:00-03:00
                    'timeZone' => 'America/Sao_Paulo',
                ],
                'end' => [
                    'dateTime' => $fimDatetime,    // Ex: 2024-05-28T09:30:00-03:00
                    'timeZone' => 'America/Sao_Paulo',
                ],
                // Lembrete via email (24 horas antes) e popup padrão (1 hora antes)
                'reminders' => [
                    'useDefault' => FALSE,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60],
                        ['method' => 'popup', 'minutes' => 60],
                    ],
                ],
            ];

            if ($emailCliente) {
                // Adiciona o cliente como convidado para que o Google mande o convite
                $eventData['attendees'] = [
                    ['email' => $emailCliente]
                ];
            }

            $event = new Google_Service_Calendar_Event($eventData);

            // 'primary' insere o evento no calendário principal da conta que autorizou o app
            $calendarId = 'primary';
            $event = $service->events->insert($calendarId, $event, ['sendUpdates' => 'all']);
            
            return $event->htmlLink;
        } catch (Exception $e) {
            error_log("Erro ao criar evento no Google Calendar: " . $e->getMessage());
            return false;
        }
    }
}

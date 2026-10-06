<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Twilio\Rest\Client;

class SmsAPI {
    private $sid;
    private $token;
    private $twilio_number;

    public function __construct() {
        // TODO: Substitua pelas suas credenciais reais do Twilio (disponível no Twilio Console)
        $this->sid = 'YOUR_TWILIO_SID';
        $this->token = 'YOUR_TWILIO_AUTH_TOKEN';
        $this->twilio_number = 'YOUR_TWILIO_PHONE_NUMBER';
    }

    public function enviarLembrete($numero_cliente, $mensagem) {
        if ($this->sid === 'YOUR_TWILIO_SID') {
            error_log("Aviso: Twilio SMS não configurado. Mensagem simulada para $numero_cliente: $mensagem");
            return false;
        }

        try {
            $client = new Client($this->sid, $this->token);
            $client->messages->create(
                $numero_cliente, // Número do destinatário em formato E.164 (ex: +5511999999999)
                [
                    'from' => $this->twilio_number,
                    'body' => $mensagem
                ]
            );
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar SMS (Twilio): " . $e->getMessage());
            return false;
        }
    }
}

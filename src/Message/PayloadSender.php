<?php

namespace Maze\MazeTv\Message;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\ResponseInterface;
use Maze\MazeTv\Client\GuzzleClient;
use PrestaShopLogger;

class PayloadSender
{
    /** @var GuzzleClient */
    private $http;

    public function __construct(GuzzleClient $client)
    {
        $this->http = $client;
    }

    public function send($message)
    {
        if (_PS_MODE_DEV_) {
            PrestaShopLogger::addLog("Payload prepared: " . json_encode($message));
        }
        try {
            $response = $this->http->post($message);
            if ($response instanceof ResponseInterface) {
                $status = $response->getStatusCode();
                if ($status === 201 && _PS_MODE_DEV_) {
                    PrestaShopLogger::addLog("Successfully sent a message");
                } else if ($status > 201) {
                    PrestaShopLogger::addLog("Unexpected mazetv response code '" . $status . "':" . (string) $response->getBody(), 3);
                }
            } else {
                PrestaShopLogger::addLog("Unexpected mazetv response", 3, null, get_class($response));
            }
        } catch (Exception $e) {
            if ($e instanceof ClientException) {
                $response =  $e->getResponse();
                PrestaShopLogger::addLog("Invalid request, response code '" . $response->getStatusCode() . "': " . (string) $response->getBody(), 3);
            } else {
                PrestaShopLogger::addLog("Failed to send message:" . $e->getMessage(), 3);
            }
        }
    }
}

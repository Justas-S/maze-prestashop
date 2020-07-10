<?php

namespace Maze\MazeTv\Client;

use GuzzleHttp\Client;
use PrestaShop\PrestaShop\Adapter\Configuration;

class GuzzleClient
{

    /** @var Client */
    private $client;

    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration, Client $client = null)
    {
        $this->configuration = $configuration;
        $this->client = $client ?? new Client([
            'base_url' => $configuration->get('mazetv_base_api_url', 'https://tv.maze.lt/'),
            'defaults' => [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Prestashop-Version' => _PS_VERSION_,
                ]
            ]
        ]);
    }

    public function post($payload)
    {
        $credentials = base64_encode(sprintf('%s:%s', $this->getAuthId(), $this->getAuthKey()));
        $request = $this->client->createRequest('POST', sprintf('streamer/%s/merch/order', $payload['streamerKey']), [
            'body'  => json_encode($payload),
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
            ]
        ]);


        return $this->client->send($request);
    }

    public function getAuthKey()
    {
        return $this->configuration->get('mazetv_authentication_key');
    }

    public function getAuthId()
    {
        return $this->configuration->get('mazetv_authentication_id');
    }
}

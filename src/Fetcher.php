<?php

namespace Balsama\Bostonplatebot;

use GuzzleHttp\Client;

class Fetcher
{

    private Client $client;
    private $plateNumber;

    // The http-accessible resource running the plate-lookup service.
    private string $host;

    public function __construct(string $plateNumber, string $env = 'lan')
    {
        $this->host = $this->findHostFromEnv($env);
        $this->client = new Client();
        $this->plateNumber = $plateNumber;
    }

    public function getPlateInfo()
    {
        $url = $this->host . '/lookup.php';
        $request = $this->client->request('POST', $url, [
            'form_params' => [
                'plate_number'  => $this->plateNumber,
            ],
        ]);

        return json_decode($request->getBody());
    }

    public function setHost(string $host)
    {
        $this->host = $host;
    }

    private function findHostFromEnv($env)
    {
        if ($env === 'lan') {
            return 'platebot.lan';
        }
        else return $env;
    }

}
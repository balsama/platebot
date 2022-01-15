<?php

namespace Balsama\Bostonplatebot;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;


class Fetcher
{

    private Client $client;
    private $plateNumber;

    public function __construct(string $plateNumber)
    {
        $this->client = new Client();
        $this->plateNumber = $plateNumber;
    }

    public function getPlateInfo()
    {
        $url = 'http://159.65.237.74/lookup.php';
        $request = $this->client->request('POST', $url, [
            'form_params' => [
                'plate_number'  => $this->plateNumber,
            ],
        ]);

        return json_decode($request->getBody());
    }

}
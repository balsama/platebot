<?php

namespace Balsama\Bostonplatebot;

use Coderjerk\BirdElephant\BirdElephant;

class Initialize
{

    public static function initialize(): BirdElephant
    {
        $storedCreds = json_decode(file_get_contents(__DIR__ . '/../../creds/platebottwitter.json'));
        $credentials = array(
            'bearer_token' => $storedCreds->bearer_token,
            'consumer_key' => $storedCreds->consumer_key,
            'consumer_secret' => $storedCreds->consumer_secret,
            'token_identifier' => $storedCreds->token_identifier,
            'token_secret' => $storedCreds->token_secret,
        );

        return new BirdElephant($credentials);
    }

}
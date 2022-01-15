<?php

namespace Balsama\Bostonplatebot;

use Coderjerk\BirdElephant\BirdElephant;

class Find
{

    private BirdElephant $twitter;

    public function __construct()
    {
        $this->twitter = Initialize::initialize();
        $this->findTweets();
    }

    public function findTweets()
    {
        $params = [
            'query' => '@bostonplatebot',
            'max_results'  => 10,
        ];

        $tweets = $this->twitter->tweets();
        $found = $tweets->search()->recent($params);

        foreach ($found->data as $mention) {
            $parts = explode(' ', $mention->text);
            $plateNumber = end($parts);
            $fetcher = new Fetcher($plateNumber);
            $plateInfo = $fetcher->getPlateInfo();
            $reply = new Reply($mention, $plateInfo->message);
        }
    }

}
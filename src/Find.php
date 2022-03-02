<?php

namespace Balsama\Bostonplatebot;

use Coderjerk\BirdElephant\BirdElephant;

class Find
{

    private BirdElephant $twitter;
    private string $env;

    public function __construct($env = 'lan')
    {
        $this->env = $env;
        $this->twitter = Initialize::initialize();
        $this->findTweets();
    }

    public function findTweets()
    {
        $user = $this->twitter->user('bostonplatebot');

        $params = [
            'tweet.fields' => 'attachments,author_id,created_at,conversation_id',
        ];

        // @todo Make $mentions into a Class
        $mentions = $user->mentions($params);
        $tweetsPossiblyNeedingAttention = [];
        foreach ($mentions->data as $mention) {
            if ((time() - strtotime($mention->created_at)) < 86400) {
                if ($this->validateTweetFormat($mention->text)) {
                    $tweetsPossiblyNeedingAttention[] = new BiElTweet(
                        $mention->author_id,
                        $mention->conversation_id,
                        new \DateTime($mention->created_at),
                        $mention->id,
                        $mention->text,
                    );
                }
            }
        }

        $needResponses = [];
        foreach ($tweetsPossiblyNeedingAttention as $tweetPossiblyNeedingAttention) {
            $possiblePreviousResponses = $this->findRecentTweetsByUserContainingString('bostonplatebot', $this->getPlateNumberFromTweet($tweetPossiblyNeedingAttention));
            if (!property_exists($possiblePreviousResponses,'data')) {
                $needResponses[] = $tweetPossiblyNeedingAttention;
            }
        }

        foreach ($needResponses as $needsResponse) {
            $plateNumber = $this->getPlateNumberFromTweet($needsResponse);
            $fetcher = new Fetcher($plateNumber, $this->env);
            $plateInfo = $fetcher->getPlateInfo();
            $reply = new Reply($needsResponse, $plateInfo);
        }
    }

    private function findRecentTweetsByUserContainingString($username, $string)
    {
        $tweets = $this->twitter->tweets();
        $params = [
            'query' => 'from:'. $username . ' ' . $string,
        ];
        return $tweets->search()->recent($params);
    }

    private function getPlateNumberFromTweet(BiElTweet $tweet)
    {
        $text = $tweet->text;
        $parts = explode(' ', $text);
        $plateNumber = end($parts);
        return $plateNumber;
    }

    private function validateTweetFormat($tweetText)
    {
        if (!str_starts_with($tweetText, '@bostonplatebot')) {
            return false;
        }
        if (count(explode(' ', $tweetText)) !== 2) {
            return false;
        }
        return true;
    }

}
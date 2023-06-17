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
    }

    public function findTweets()
    {
        $user = $this->twitter->user('bostonplatebot');

        $params = [
            'tweet.fields' => 'attachments,author_id,created_at,conversation_id',
        ];

        // @todo Make $mentions into a Class
        $mentions = $user->mentions($params);
        $mentions = json_decode(file_get_contents(__DIR__ . '/../example-responses/mentions-in-reply.json'));

        $tweetsPossiblyNeedingAttention = [];
        foreach ($mentions->data as $mention) {
            if ((time() - strtotime($mention->created_at)) < 8640000) {
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

        $this->precessTweetsThatNeedResponses($needResponses);
    }

    public function precessTweetsThatNeedResponses($needResponses) {
        foreach ($needResponses as $needsResponse) {
            /* @var \Balsama\Bostonplatebot\BiElTweet $needsResponse */
            $this->processTweetThatNeedsRespons($needsResponse);
        }
    }

    public function processTweetThatNeedsRespons(BiElTweet $tweetToRespondTo)
    {
        $plateNumber = $this->getPlateNumberFromTweet($tweetToRespondTo);
        $fetcher = new Fetcher($plateNumber, $this->env);
        $plateInfo = $fetcher->getPlateInfo();
        return $reply = new Reply($tweetToRespondTo, $plateInfo);
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
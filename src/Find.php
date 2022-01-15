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
        $user = $this->twitter->user('bostonplatebot');

        $params = [
            'tweet.fields' => 'attachments,author_id,created_at,conversation_id',
        ];

        $mentions = $user->mentions($params);
        $tweetsPossiblyNeedingAttention = [];
        foreach ($mentions->data as $mention) {
            if ((time() - strtotime($mention->created_at)) < 1800) {
                $tweetsPossiblyNeedingAttention[] = $mention;
            }
        }

        // Find tweets by bostonplatebot with the platenumber in it. If it exists and it's newer than the original tweet, we're all set.
        foreach ($tweetsPossiblyNeedingAttention as $tweetPossiblyNeedingAttention) {
            $possiblePreviousResponses = $this->findRecentTweetsByUserContainingString('bostonplatebot', $this->getPlateNumberFromTweet($tweetPossiblyNeedingAttention));
            if (!property_exists($possiblePreviousResponses,'data')) {
                $needResponses[] = $tweetPossiblyNeedingAttention;
            }
        }

        foreach ($needResponses as $needsResponse) {
            $plateNumber = $this->getPlateNumberFromTweet($needsResponse);
            $fetcher = new Fetcher($plateNumber);
            $plateInfo = $fetcher->getPlateInfo();
            $reply = new Reply($needsResponse, $plateInfo->message);
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

    private function getPlateNumberFromTweet(\stdClass $tweet)
    {
        $text = $tweet->text;
        $parts = explode(' ', $text);
        $plateNumber = end($parts);
        return $plateNumber;
    }

}
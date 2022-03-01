<?php

namespace Balsama\Bostonplatebot;

use Coderjerk\BirdElephant\BirdElephant;
use Coderjerk\BirdElephant\Compose\Tweet;
use Coderjerk\BirdElephant\Compose\Reply as TweetReply;

class Reply
{

    private BirdElephant $twitter;
    private string $fullTweetText;
    private array $tweetTexts;

    public function __construct(BiElTweet $triggerTweet, string $fullTweetText)
    {
        $this->twitter = Initialize::initialize();
        $this->fullTweetText = $fullTweetText;
        $this->tweetTexts = str_split($this->fullTweetText, 230);
        $initialTweet = $this->tweetInitialTweet($triggerTweet->id);

        if (count($this->tweetTexts) > 1) {
            $this->sendReplies($initialTweet);
        }
    }

    private function tweetInitialTweet($triggerTweetId)
    {
        return $this->postReply($triggerTweetId, $this->tweetTexts[0]);
    }

    private function sendReplies($tweetToReplyTo)
    {
        $replies = $this->tweetTexts;
        array_shift($replies);
        foreach ($replies as $reply) {
            $tweet = $this->postReply($tweetToReplyTo->data->id, $reply);
            $tweetToReplyTo = $tweet;
        }
    }

    private function postReply($tweetToReplyToId, $replyText)
    {
        $reply = new TweetReply();
        $reply->inReplyToTweetId($tweetToReplyToId);
        $tweet = new Tweet();
        $tweet->text($replyText);
        $tweet->reply($reply);
        return $this->twitter->tweets()->tweet($tweet);
    }

}
<?php

namespace Balsama\Bostonplatebot;

use Coderjerk\BirdElephant\BirdElephant;
use Coderjerk\BirdElephant\Compose\Tweet;
use Coderjerk\BirdElephant\Compose\Reply as TweetReply;
use Coderjerk\BirdElephant\User;

class Reply
{

    private BirdElephant $twitter;
    private string $triggerTweetId;

    public function __construct(BiElTweet $triggerTweet, $plateInfo)
    {
        $this->twitter = Initialize::initialize();
        $this->triggerTweetId = $triggerTweet->id;

        $this->chunkTweets($plateInfo);

        if ($plateInfo->tickets) {
            $this->retweetOriginal();
        }
    }

    private function chunkTweets(\stdClass $plateInfo): void
    {
        $tweets = [];
        $tweets[] = $this->generateFirstTweet($plateInfo);
        if ($plateInfo->tickets) {
            $ticketsTweets = $this->ticketsToTweetStrings($plateInfo->tickets);
            $tweets = array_merge($tweets, $ticketsTweets);
        }

        $tweetToReplyToId = $this->triggerTweetId;
        foreach ($tweets as $tweet) {
            $tweet = $this->postReply($tweetToReplyToId, $tweet);
            $tweetToReplyTo = $tweet;
            $tweetToReplyToId = $tweetToReplyTo->data->id;
        }
    }

    private function ticketsToTweetStrings(array $tickets): array
    {
        $tickets = array_reverse($tickets);
        $formattedTickets = [];
        foreach ($tickets as $ticket) {
            $formattedTickets[] = $this->ticketToString($ticket);
        }
        $i = 0;
        $n = 0;
        $chunkedFormattedTickets = [];
        foreach ($formattedTickets as $formattedTicket) {
            $chunkedFormattedTickets[$n][$i] = $formattedTicket;
            $i++;
            if ($i == 3) {
                $chunkedFormattedTickets[$n] = implode(PHP_EOL, $chunkedFormattedTickets[$n]);
                $i = 0;
                $n++;
            }
        }
        if (is_array($chunkedFormattedTickets[$n])) {
            $chunkedFormattedTickets[$n] = implode(PHP_EOL, $chunkedFormattedTickets[$n]);
        }

        return $chunkedFormattedTickets;
    }

    private function ticketToString(\stdClass $ticket):string
    {
        $format = '- %s: $%d, %s %s at %s.';
        return sprintf($format, $ticket->infraction, $ticket->fine, $ticket->infraction_date, $ticket->infraction_time, $ticket->infraction_address);
    }

    private function generateFirstTweet(\stdClass $plateInfo):string
    {
        if (!$plateInfo->found) {
            if ($plateInfo->tickets) {
                $totalTweets = round(((count($plateInfo->tickets))/3), 0) + 1;
                $format = 'Unable to find current balance for plate %s in the system, but we did find the following %d tickets associated with that plate.' . PHP_EOL . PHP_EOL . '🧵👇 [1/%d]';
                $firstTweet = sprintf($format, strtoupper($plateInfo->plate_number), count($plateInfo->tickets), $totalTweets);
            }
            else {
                $format = 'Unable to find current balance for plate %s or any tickets associated with that plate.🤷‍♀️';
                $firstTweet = sprintf($format, strtoupper($plateInfo->plate_number));
            }
        }
        else {
            if ($plateInfo->tickets) {
                $totalTweets = round(((count($plateInfo->tickets))/3), 0) + 1;
                $format = 'Plate %s has a current balance of $%4.2f and we found %d tickets associated with that plate.' . PHP_EOL . PHP_EOL . '🧵👇 [1/%d]';
                $firstTweet = sprintf($format, strtoupper($plateInfo->plate_number), $plateInfo->balance, count($plateInfo->tickets), $totalTweets);
            }
            else {
                $format = 'Plate %s has a current balance of $%4.2f and we found zero tickets associated with that plate.';
                $firstTweet = sprintf($format, strtoupper($plateInfo->plate_number), $plateInfo->balance);
            }
        }
        return $firstTweet;
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

    private function retweetOriginal()
    {
        $this->twitter->user('bostonplatebot')->retweet($this->triggerTweetId);
    }

}
<?php

namespace Balsama\Bostonplatebot;

class BiElTweet
{

    public function __construct(
        public string $authorId,
        public string $conversationId,
        public \DateTime $createdAt,
        public string $id,
        public string $text,
    )
    {}

    public function getCreatedDateStandardFormat()
    {
        return $this->createdAt->format('DMY H:M:S');
    }

}
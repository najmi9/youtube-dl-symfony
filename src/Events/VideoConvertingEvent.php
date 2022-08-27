<?php

declare(strict_types=1);

namespace App\Events;

class VideoConvertingEvent
{
    private array $lastOutput;

    private array $topics;

    public function __construct(array $lastOutput, array $topics)
    {
        $this->lastOutput = $lastOutput;
        $this->topics = $topics;
    }

    public function getLastOutput(): array
    {
        return $this->lastOutput;
    }

    public function getTopics(): array
    {
        return $this->topics;
    }
}

<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PostPublished extends ShouldBeStored
{
    public string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }
}

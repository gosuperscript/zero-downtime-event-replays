<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CommentPlaced extends ShouldBeStored
{
    public string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }
}

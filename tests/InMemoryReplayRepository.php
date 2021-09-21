<?php

namespace Mannum\ZeroDowntimeEventReplays\Tests;

use Mannum\ZeroDowntimeEventReplays\Replay;
use Mannum\ZeroDowntimeEventReplays\Repositories\ReplayRepository;

class InMemoryReplayRepository implements ReplayRepository
{
    public array $replays = [];

    public function getReplayByKey(string $key): ?Replay
    {
        return $this->replays[$key] ?? null;
    }

    public function persist(Replay $replay): void
    {
        $this->replays[$replay->key] = $replay;
    }
}

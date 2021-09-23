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

    public function getLiveReplaysForProjector(string $class): array
    {
        return [];
    }

    public function delete(string $key): void
    {
        unset($this->replays[$key]);
    }
}

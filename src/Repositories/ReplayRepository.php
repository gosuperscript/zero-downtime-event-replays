<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Repositories;

use Gosuperscript\ZeroDowntimeEventReplays\Replay;

interface ReplayRepository
{
    public function getReplayByKey(string $key): ?Replay;

    public function persist(Replay $replay);

    public function getLiveReplaysForProjector(string $class): array;

    public function delete(string $key): void;
}

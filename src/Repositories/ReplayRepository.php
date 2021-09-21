<?php

namespace Mannum\ZeroDowntimeEventReplays\Repositories;

use Mannum\ZeroDowntimeEventReplays\Replay;

interface ReplayRepository
{

    public function getReplayByKey(string $key) : ?Replay;

    public function persist(Replay $replay);
}
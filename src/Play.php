<?php

namespace Gosuperscript\ZeroDowntimeEventReplays;

use Carbon\CarbonInterface;

class Play
{
    public int $lastProjected = 0;
    public CarbonInterface $started_at;
    public ?CarbonInterface $finished_at = null;

    public function __construct(public string $id, public int $from = 0, ?CarbonInterface $started_at = null)
    {
        $this->started_at = $started_at ?? now();
    }

    public function setLastProjectedEvent(int $lastProjected)
    {
        $this->lastProjected = $lastProjected;
    }

    public function finished(?CarbonInterface $finished_at = null)
    {
        $this->finished_at = $finished_at ?? now();
    }
}

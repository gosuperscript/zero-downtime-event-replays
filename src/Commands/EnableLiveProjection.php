<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Commands;

use Gosuperscript\ZeroDowntimeEventReplays\ReplayManager;
use Illuminate\Console\Command;

class EnableLiveProjection extends Command
{
    protected $signature = 'replay-manager:enable-projections {key}';

    protected $description = 'Replay manager: enable projectors to play to projection';

    public function handle(ReplayManager $replayManager)
    {
        $key = $this->argument('key');

        $lag = $replayManager->getReplayLag($key);

        if ($lag > 0) {
            if ($this->confirm("Lag of {$lag} events detected. Replay first before starting live projectors?")) {
                $replayManager->startReplay($key);
                $this->info("replay done");
            }
        }
        $replayManager->startProjectingToReplay($key);

        $this->info("done, projection is now also playing to key {$key}");
    }
}

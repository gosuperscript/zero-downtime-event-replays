<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Commands;

use Gosuperscript\ZeroDowntimeEventReplays\ReplayManager;
use Illuminate\Console\Command;

class PutReplayLive extends Command
{
    protected $signature = 'replay-manager:put-live {key} {--force}';

    protected $description = 'Replay manager: promote replay to production';

    public function handle(ReplayManager $replayManager)
    {
        $key = $this->argument('key');

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to promote the replay to production?")) {
            $this->warn("Aborted");

            return;
        }
        $replayManager->putReplayLive($key);

        $this->info("done, replay {$key} promoted");
    }
}

<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Commands;

use Illuminate\Console\Command;
use Gosuperscript\ZeroDowntimeEventReplays\ReplayManager;

class DeleteReplay extends Command
{
    protected $signature = 'replay-manager:delete {key} {--force}';

    protected $description = 'Replay manager: delete replay and associated db tables';

    public function handle(ReplayManager $replayManager)
    {
        $key = $this->argument('key');

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to delete the replay?")) {
            $this->warn("Aborted");

            return;
        }
        $replayManager->removeReplay($key);

        $this->info("done, replay {$key} deleted");
    }
}

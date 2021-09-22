<?php

namespace Mannum\ZeroDowntimeEventReplays\Commands;

use Illuminate\Console\Command;
use Mannum\ZeroDowntimeEventReplays\ReplayManager;
use Spatie\EventSourcing\StoredEvents\StoredEvent;

class ReplayCommand extends Command
{
    protected $signature = 'replay-manager:replay {key} {--restart}';

    protected $description = 'Replay manager: replay to projection';
    private ReplayManager $replayManager;

    public function handle(ReplayManager $replayManager): void
    {
        $this->replayManager = $replayManager;
        $key = $this->argument('key');

        $replay = $this->replayManager->getReplay($key);
        if (! $replay) {
            $this->warn("Replay with key {$key} not found.");

            return;
        }
        if ($replay->activePlayId !== null) {
            $this->warn("replay still in progress.");

            return;
        }

        if ($replay->projectionsEnabled) {
            $this->warn("Live projections already projecting. Stop this first.");

            return;
        }

        if ($this->option('restart')) {
            $replay->lastProjectedEvent = 0;
            $this->replayManager->resetReplay($key);
            $this->info("replay reset");
        }

        $lag = $this->replayManager->getReplayLag($key);
        $bar = $this->output->createProgressBar($lag);
        $onEventReplayed = function (StoredEvent $storedEvent) use ($bar) {
            $bar->advance();
        };

        $this->replayManager->startReplay($key, $onEventReplayed);
        $bar->finish();
    }
}

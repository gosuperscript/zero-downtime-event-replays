<?php

namespace Mannum\ZeroDowntimeEventReplays\Commands;

use Illuminate\Support\Collection;
use Mannum\ZeroDowntimeEventReplays\ZeroDowntimeProjector;
use Spatie\EventSourcing\Console\ReplayCommand;
use Spatie\EventSourcing\Projectionist;
use Spatie\EventSourcing\StoredEvents\Repositories\StoredEventRepository;
use Spatie\EventSourcing\StoredEvents\StoredEvent;

class ZeroDowntimeEventReplaysCommand extends ReplayCommand
{
    public $signature = 'event-sourcing:replay-without-downtime {projector?*}
                            {--from=0 : Replay events starting from this event number}
                            {--replayKey= : Replay events starting from this event number}
                            {--stored-event-model= : Replay events from this store}';

    public $description = 'My command';

    protected ?Projectionist $projectionist;
    private int $maxEventId = 0;

    public function handle(Projectionist $projectionist): void
    {
        $this->projectionist = $projectionist;

        $projectors = $this->selectProjectors($this->argument('projector'));

        foreach ($projectors as $projector) {
            if(!$projector instanceof ZeroDowntimeProjector)
            {
                $class = get_class($projector);
                $this->warn("Projector {$class} is not implementing the ZeroDowntimeProjector interface and therefore cant be used in zero downtime replays");
                return;
            }
        }

        if (is_null($projectors)) {
            $this->warn('No projectors selected to replay events to!');
            return;
        }

        $replayKey = $this->option('replayKey') ?? \Str::random(6);

        $this->info("project to replay with key {$replayKey}");

        $projectors->each(function (ZeroDowntimeProjector $zeroDowntimeProjector) use ($replayKey) {
            $zeroDowntimeProjector->useConnection($replayKey);
        });


        $this->replay($projectors, (int)$this->option('from'));


    }

    public function replay(Collection $projectors, int $startingFrom): void
    {
        $repository = app(StoredEventRepository::class);
        $replayCount = $repository->countAllStartingFrom($startingFrom);

        if ($replayCount === 0) {
            $this->warn('There are no events to replay');

            return;
        }

        $this->comment("Replaying {$replayCount} events...");

        $bar = $this->output->createProgressBar($replayCount);
        $onEventReplayed = function (StoredEvent $storedEvent) use ($bar) {
            $this->maxEventId = max($this->maxEventId, $storedEvent->id);
            $bar->advance();
        };

        $this->projectionist->replay($projectors, $startingFrom, $onEventReplayed);

        $bar->finish();
        $this->line('');
        $this->line('');

//        $this->emptyLine(2);
//        $this->comment('All done!');

        dd($this->maxEventId);
    }
}

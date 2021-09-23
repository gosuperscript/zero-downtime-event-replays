<?php

namespace Mannum\ZeroDowntimeEventReplays\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Mannum\ZeroDowntimeEventReplays\ReplayManager;
use Mannum\ZeroDowntimeEventReplays\Projectors\ZeroDowntimeProjector;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\Projectionist;

class CreateReplay extends Command
{
    protected $signature = 'replay-manager:create {key?} {projector?*}';
    private Projectionist $projectionist;

    public function handle(ReplayManager $replayManager, Projectionist $projectionist)
    {
        $this->projectionist = $projectionist;
        $key = $this->argument('key') ?? $this->ask('Unique replay key?');

        $projectors = count($this->argument('projector')) !== 0 ? $this->argument('projector') : $this->askForProjectors();
        if (count($projectors) !== 0) {
            $projectors = $this->parseProjectors($projectors);
            if (! $projectors) {
                return;
            }
        }

        $replayManager->createReplay($this->argument('key'), $projectors->map(function (Projector $projector) {
            return $projector->getName();
        })->toArray());

        $this->info("replay created");
    }

    private function parseProjectors(array $projectorClassNames): ?Collection
    {
        $projectors = collect($projectorClassNames)
            ->map(fn (string $projectorName) => ltrim($projectorName, '\\'))
            ->map(function (string $projectorName) {
                if (! $projector = $this->projectionist->getProjector($projectorName)) {
                    throw new Exception("Projector {$projectorName} not found. Did you register it?");
                }

                return $projector;
            });

        foreach ($projectors as $projector) {
            if (! $projector instanceof ZeroDowntimeProjector) {
                $class = get_class($projector);
                $this->warn("Projector {$class} is not implementing the ZeroDowntimeProjector interface and therefore cant be used in zero downtime replays");

                return null;
            }
        }

        return $projectors;
    }

    private function askForProjectors()
    {
        $options = collect($this->projectionist->getProjectors()->toArray())->filter(function (Projector $projector) {
            return $projector instanceof ZeroDowntimeProjector;
        })->mapWithKeys(function (Projector $projector) {
            return [$projector->getName() => $projector->getName()];
        });

        return $this->choice(
            'Select projectors to run?',
            $options->toArray(),
            default: null,
            attempts: null,
            multiple: true
        );
    }
}

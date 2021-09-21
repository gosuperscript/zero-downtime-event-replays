<?php

namespace Mannum\ZeroDowntimeEventReplays;

use Mannum\ZeroDowntimeEventReplays\Exceptions\CreateReplayException;
use Mannum\ZeroDowntimeEventReplays\Repositories\ReplayRepository;
use Spatie\EventSourcing\Projectionist;
use Spatie\EventSourcing\StoredEvents\Repositories\StoredEventRepository;
use Spatie\EventSourcing\StoredEvents\StoredEvent;

class ReplayManager
{
    private Projectionist $projectionist;
    private ReplayRepository $replayRepository;
    private StoredEventRepository $storedEventRepository;

    public function __construct(ReplayRepository $replayRepository, Projectionist $projectionist, StoredEventRepository $storedEventRepository)
    {
        $this->projectionist = $projectionist;
        $this->replayRepository = $replayRepository;
        $this->storedEventRepository = $storedEventRepository;
    }

    /**
     * @throws CreateReplayException
     */
    public function createReplay(string $key, array $projectorClassNames): void
    {
        $replay = $this->replayRepository->getReplayByKey($key);
        if ($replay) {
            throw CreateReplayException::replayAlreadyExists($key);
        }

//        verify projectors are configured
        $projectors = collect($projectorClassNames)
            ->map(fn (string $projectorName) => ltrim($projectorName, '\\'))
            ->each(function (string $projectorName) {
                if (! $this->projectionist->getProjector($projectorName)) {
                    throw new \Exception("Projector {$projectorName} not found. Did you register it?");
                }
            });

        $replay = new Replay($key);
        foreach ($projectors as $projectorName) {
            $replay->addProjector($projectorName);
        }

        $this->replayRepository->persist($replay);
//
//        $projectors->each(function (ZeroDowntimeProjector $zeroDowntimeProjector) use ($key) {
//            $zeroDowntimeProjector->useConnection($key);
//        });

//        $this->projectionist->replay($projectors);
//        $replay = new Replay($key);
//        $replay->addProjectors($projectors);
    }

    public function startReplay(string $key, int $fromEventId = 0)
    {
        $replay = $this->replayRepository->getReplayByKey($key);
        if (! $replay->readyToStart()) {
            throw new \Exception("cannot start");
        }
        $projectors = collect($replay->projectors)->map(function (string $projectorName) {
            return $this->projectionist->getProjector($projectorName);
        })->each(function (ZeroDowntimeProjector $zeroDowntimeProjector) use ($key) {
            $zeroDowntimeProjector->useConnection($key);
        });

        $onEventReplayed = function (StoredEvent $storedEvent) use (&$replay) {
            $replay->setLastProjectedEventNumber($storedEvent->id);
            // only persist once every 50 events, to save repo calls
            if (($storedEvent->id % 50) == 0) {
                $this->replayRepository->persist($replay);
            }
        };

        $replay->started($fromEventId);

        try {
            $this->replayRepository->persist($replay);
        } catch (\Exception $e) {
            // Persist so we always have the latest replayed event ID even on failure
            $this->replayRepository->persist($replay);

            throw $e;
        }

        $this->projectionist->replay($projectors, $fromEventId, $onEventReplayed);

        $replay->finished();
        $this->replayRepository->persist($replay);
    }

    public function getReplayLag(string $key): int
    {
        $replay = $this->getReplay($key);
        if (! $replay) {
            throw new \Exception("Replay not found");
        }

        return $this->storedEventRepository->countAllStartingFrom($replay->lastProjectedEvent);
    }

    public function getReplay(string $key): ?Replay
    {
        return $this->replayRepository->getReplayByKey($key);
    }

    public function startProjectingToReplay(string $key): void
    {
        // todo, should lock events table here, to prevent race conditions
        if ($this->getReplayLag($key) !== 0) {
            throw new \Exception("Projection is lagging, make sure projection is up to date first before enabling projections");
        }
        $replay = $this->replayRepository->getReplayByKey($key);
        $replay->enableProjections();
        $this->replayRepository->persist($replay);
    }

    public function putReplayLive(string $key)
    {
    }

    public function removeReplay(string $key) : void
    {
    }
}

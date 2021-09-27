<?php

namespace Gosuperscript\ZeroDowntimeEventReplays;

use Carbon\CarbonInterface;
use Ramsey\Uuid\Uuid;

class Replay
{
    public string $key;
    public array $projectors = [];
    public int $lastProjectedEvent = 0;
    public array $plays = [];
    public ?string $activePlayId = null;
    public bool $projectionsEnabled = false;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function addProjector(string $projectorClassName)
    {
        $this->projectors[] = $projectorClassName;
    }

    public function readyToStart(): bool
    {
        return $this->activePlayId === null;
    }

    public function setLastProjectedEventNumber(int $lastProjectedEvent)
    {
        $this->lastProjectedEvent = $lastProjectedEvent;
        $play = $this->getActivePlay();
        if ($play) {
            $play->setLastProjectedEvent($lastProjectedEvent);
            $this->plays[$play->id] = $play;
        }
    }

    public function started(int $fromEventId, ?CarbonInterface $startedAt = null)
    {
        $playId = Uuid::uuid4()->toString();
        $play = new Play($playId, $fromEventId, $startedAt);
        $this->plays[$playId] = $play;
        $this->activePlayId = $playId;
    }

    public function finished(?CarbonInterface $finishedAt = null)
    {
        /** @var Play $play */
        $play = $this->getActivePlay();
        if (! $play) {
            return;
        }
        $play->finished($finishedAt);
        $this->plays[$play->id] = $play;

        $this->activePlayId = null;
    }

    public function getActivePlay(): ?Play
    {
        if (! $this->activePlayId) {
            return null;
        }

        return $this->plays[$this->activePlayId];
    }

    public function enableProjections()
    {
        $this->projectionsEnabled = true;
    }

    public function containsProjector(string $projectorName): bool
    {
        return in_array($projectorName, $this->projectors);
    }
}

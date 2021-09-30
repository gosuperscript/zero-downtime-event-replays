<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests\Fakes;

use Gosuperscript\ZeroDowntimeEventReplays\Projectors\ZeroDowntimeProjector;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class FakeProjector extends Projector implements ZeroDowntimeProjector
{
    public ?string $connection = null;
    public bool $promoted = false;
    private bool $removed = false;

    public function hasBeenPutLive(): bool
    {
        return $this->promoted;
    }

    public function hasBeenRemoved(): bool
    {
        return $this->removed;
    }

    public function forReplay(): ZeroDowntimeProjector
    {
        return $this;
    }

    public function useConnection(string $connection): ZeroDowntimeProjector
    {
        $this->connection = $connection;

        return $this;
    }

    public function promoteConnectionToProduction(): void
    {
        $this->promoted = true;
    }

    public function removeConnection()
    {
        $this->removed = true;
    }
}

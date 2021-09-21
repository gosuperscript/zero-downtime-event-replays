<?php

namespace Mannum\ZeroDowntimeEventReplays\Tests\Fakes;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\Projectionist;

class FakeProjectionist extends Projectionist
{
    public array $projectors = [];

    public function __construct()
    {
    }

    public function addProjector($projector): Projectionist
    {
        $this->projectors[$projector] = new FakeProjector();
        return $this;
    }

    public function getProjector(string $name): ?Projector
    {
        return $this->projectors[$name] ?? null;
    }
}

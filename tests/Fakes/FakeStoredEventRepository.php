<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests\Fakes;

use Illuminate\Support\LazyCollection;
use Spatie\EventSourcing\StoredEvents\Repositories\StoredEventRepository;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Spatie\EventSourcing\StoredEvents\StoredEvent;

class FakeStoredEventRepository implements StoredEventRepository
{
    public array $countsStartingFrom = [];

    public function retrieveAll(string $uuid = null): LazyCollection
    {
        // TODO: Implement retrieveAll() method.
    }

    public function retrieveAllStartingFrom(int $startingFrom, string $uuid = null): LazyCollection
    {
        // TODO: Implement retrieveAllStartingFrom() method.
    }

    public function retrieveAllAfterVersion(int $aggregateVersion, string $aggregateUuid): LazyCollection
    {
        // TODO: Implement retrieveAllAfterVersion() method.
    }

    public function countAllStartingFrom(int $startingFrom, string $uuid = null): int
    {
        return $this->countsStartingFrom[$startingFrom] ?? 0;
    }

    public function persist(ShouldBeStored $event, string $uuid = null, int $aggregateVersion = null): StoredEvent
    {
        // TODO: Implement persist() method.
    }

    public function persistMany(array $events, string $uuid = null, int $aggregateVersion = null): LazyCollection
    {
        // TODO: Implement persistMany() method.
    }

    public function update(StoredEvent $storedEvent): StoredEvent
    {
        // TODO: Implement update() method.
    }

    public function getLatestAggregateVersion(string $aggregateUuid): int
    {
        // TODO: Implement getLatestAggregateVersion() method.
    }

    public function setCountStartingFrom(int $startingFrom, int $count): void
    {
        $this->countsStartingFrom[$startingFrom] = $count;
    }
}

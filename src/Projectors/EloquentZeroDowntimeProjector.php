<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Projectors;

use Exception;
use Gosuperscript\ZeroDowntimeEventReplays\Replay;
use Gosuperscript\ZeroDowntimeEventReplays\Repositories\ReplayRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\StoredEvents\StoredEvent;

abstract class EloquentZeroDowntimeProjector extends Projector implements ZeroDowntimeProjector
{
    use HandlesEvents {
        handle as private handleEvent;
    }

    protected ?string $connection = null;

    private bool $isReplay = false;

    abstract public function models(): array;

    public function forReplay(): ZeroDowntimeProjector
    {
        $this->isReplay = true;

        return $this;
    }

    public function useConnection(string $connection): self
    {
        $this->connection = $connection;
        $this->configureConnections();

        return $this;
    }

    public function promoteConnectionToProduction(): void
    {
        foreach ($this->models() as $model) {
            if (! $model instanceof Model) {
                throw new Exception("models in the models method should extend eloquents model class");
            }
            $ghostConnectionTable = config('database.connections.' . $this->getGhostConnectionForModel($model) . '.prefix') . $model->getTable();

            DB::connection($model->getConnectionName())->statement("ALTER TABLE {$model->getTable()} RENAME TO {$ghostConnectionTable}_old_prod;");
            DB::connection($model->getConnectionName())->statement("ALTER TABLE {$ghostConnectionTable} RENAME TO {$model->getTable()};");
            DB::connection($model->getConnectionName())->statement("ALTER TABLE {$ghostConnectionTable}_old_prod RENAME TO {$ghostConnectionTable};");
        }
    }

    public function removeConnection(): void
    {
        foreach ($this->models() as $model) {
            if (! $model instanceof Model) {
                throw new Exception("models in the models method should extend eloquents model class");
            }
            $ghostConnectionTable = config('database.connections.' . $this->getGhostConnectionForModel($model) . '.prefix') . $model->getTable();
            DB::connection($model->getConnectionName())->statement("DROP TABLE IF EXISTS {$ghostConnectionTable}");
        }
    }

    public function handle(StoredEvent $storedEvent)
    {
        $this->handleEvent($storedEvent);
        if (! $this->isReplay) {
            // project to live parallel projectors
            $replayRepository = resolve(ReplayRepository::class);
            $replays = $replayRepository->getLiveReplaysForProjector($this->getName());
            collect($replays)->each(function (Replay $replay) use ($storedEvent) {
                self::handleOnConnection($replay->key, $storedEvent);
            });
        }
    }

    public static function handleOnConnection(string $connection, StoredEvent $storedEvent)
    {
        $projector = resolve(get_called_class());
        $projector->useConnection($connection);
        $projector->handleEvent($storedEvent);
    }

    private function configureConnections(): void
    {
        foreach ($this->models() as $model) {
            if (! $model instanceof Model) {
                throw new Exception("models in the models method should extend eloquents model class");
            }
            $defaultConnection = $model->getConnectionName() ?? config('database.default');
            $ghostConnection = $this->getGhostConnectionForModel($model);

            if (! array_key_exists($ghostConnection, config('database.connections'))) {
                $connectionClone = config('database.connections.' . $defaultConnection);
                $connectionClone['prefix'] = 'replay_' . $this->connection . '_' . $connectionClone['prefix'];

                config(['database.connections.' . $ghostConnection => $connectionClone]);
            }

            $this->createTableForModel($model);
        }
    }

    private function createTableForModel(Model $model)
    {
        $ghostConnection = $this->getGhostConnectionForModel($model);
        $ghostConnectionPrefix = config('database.connections.' . $ghostConnection . '.prefix');
        $newTable = $ghostConnectionPrefix . $model->getTable();
        //  todo: Deal with postgres auto increments
        DB::connection($model->getConnectionName())->statement("CREATE TABLE IF NOT EXISTS {$newTable} (LIKE {$model->getTable()} INCLUDING ALL);");
    }

    public function getGhostConnectionForModel(Model $model): string
    {
        $defaultConnection = $model->getConnectionName() ?? config('database.default');

        return 'replay_' . $this->connection . '_' . $defaultConnection;
    }
}

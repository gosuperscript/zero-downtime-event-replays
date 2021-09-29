<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Projectors;

use Exception;
use Gosuperscript\ZeroDowntimeEventReplays\Replay;
use Gosuperscript\ZeroDowntimeEventReplays\Repositories\ReplayRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    private array $fkStatements = [];

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
        collect($this->models())
            ->groupBy(function (Model $model) {
                return $model->getConnectionName();
            })->each(function ($models, $connection) {
                $tables = [];
                foreach ($models as $model) {
                    if (! $model instanceof Model) {
                        throw new Exception("models in the models method should extend eloquents model class");
                    }
                    $ghostConnectionTable = config('database.connections.' . $this->getGhostConnectionForModel($model) . '.prefix') . $model->getTable();

                    $defaultValues = DB::connection($model->getConnectionName())->select("SELECT column_name, column_default FROM information_schema.columns WHERE (table_schema, table_name) = ('public', '{$ghostConnectionTable}') AND column_default is not null;");
                    $statements = [];
                    collect($defaultValues)->filter(function ($defaultValue) {
                        return Str::contains($defaultValue->column_default, "_seq") && Str::contains($defaultValue->column_default, 'nextval(');
                    })->each(function ($defaultValue) use ($model, $ghostConnectionTable, &$statements) {
                        $statements[] = "DROP SEQUENCE IF EXISTS {$ghostConnectionTable}_{$defaultValue->column_name}_seq";
                    });
                    $tables[] = $ghostConnectionTable;
                }

                $tablesString = implode(',', $tables);

                DB::connection($connection)->statement("DROP TABLE IF EXISTS {$tablesString} CASCADE");
                foreach ($statements as $statement) {
                    DB::connection($connection)->statement($statement);
                }
            });
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
                $connectionClone['prefix'] = 'replay_' . $this->connection . '_' . ($connectionClone['prefix'] ?? '');

                config(['database.connections.' . $ghostConnection => $connectionClone]);
            }

            $this->createTableForModel($model);
        }
        foreach ($this->fkStatements as $connection => $statements) {
            foreach ($statements as $statement) {
                DB::connection($connection)->statement($statement);
            }
        }
        $this->fkStatements = [];
    }

    private function createTableForModel(Model $model)
    {
        $ghostConnection = $this->getGhostConnectionForModel($model);
        $ghostConnectionPrefix = config('database.connections.' . $ghostConnection . '.prefix');
        $newTable = $ghostConnectionPrefix . $model->getTable();
        //  todo: Deal with postgres auto increments
        DB::connection($model->getConnectionName())->statement("CREATE TABLE IF NOT EXISTS {$newTable} (LIKE {$model->getTable()} INCLUDING ALL);");

        // fix sequence
        $defaultValues = DB::connection($model->getConnectionName())->select("SELECT column_name, column_default FROM information_schema.columns WHERE (table_schema, table_name) = ('public', '{$newTable}') AND column_default is not null;");
        collect($defaultValues)->filter(function ($defaultValue) {
            return Str::contains($defaultValue->column_default, "_seq") && Str::contains($defaultValue->column_default, 'nextval(');
        })->each(function ($defaultValue) use ($model, $newTable) {
            DB::connection($model->getConnectionName())->statement("ALTER TABLE {$newTable} ALTER {$defaultValue->column_name} DROP DEFAULT");
            DB::connection($model->getConnectionName())->statement("CREATE SEQUENCE {$newTable}_{$defaultValue->column_name}_seq;");
            DB::connection($model->getConnectionName())->statement("ALTER TABLE {$newTable} ALTER {$defaultValue->column_name} SET DEFAULT nextval('{$newTable}_{$defaultValue->column_name}_seq');");
        });

        // list all foreign keys, and copy them
        if (! array_key_exists($model->getConnectionName(), $this->fkStatements)) {
            $this->fkStatements[$model->getConnectionName()] = [];
        }
        $this->fkStatements[$model->getConnectionName()] = array_merge($this->fkStatements[$model->getConnectionName()], $this->getForeignKeyStatementsForModel($model, $ghostConnectionPrefix));
    }

    public function getGhostConnectionForModel(Model $model): string
    {
        $defaultConnection = $model->getConnectionName() ?? config('database.default');

        return 'replay_' . $this->connection . '_' . $defaultConnection;
    }

    private function getForeignKeyStatementsForModel(Model $model, string $prefix): array
    {
        $foreignKeys = DB::connection($model->getConnectionName())
            ->select("
            SELECT
                tc.table_schema,
                tc.constraint_name,
                tc.table_name,
                kcu.column_name,
                ccu.table_schema AS foreign_table_schema,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
                  AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name='{$model->getTable()}';");

        $foreignKeyStatementsWithPrefix = collect($foreignKeys)
            ->filter(function ($fk){
                return collect($this->models())->map(function (Model $model){
                    return $model->getTable();
                })->contains($fk->foreign_table_name);
            })->map(function ($fk) use ($prefix) {
            return "ALTER TABLE {$prefix}{$fk->table_name}
                    ADD CONSTRAINT {$fk->constraint_name}
                    FOREIGN KEY ({$fk->column_name})
                    REFERENCES {$prefix}{$fk->foreign_table_name} ({$fk->foreign_column_name});";
        })->toArray();

        $foreignKeyStatementsWithoutPrefix = collect($foreignKeys)
            ->filter(function ($fk){
                return !collect($this->models())->map(function (Model $model){
                    return $model->getTable();
                })->contains($fk->foreign_table_name);
            })->map(function ($fk) use ($prefix) {
                return "ALTER TABLE {$prefix}{$fk->table_name}
                    ADD CONSTRAINT {$fk->constraint_name}
                    FOREIGN KEY ({$fk->column_name})
                    REFERENCES {$fk->foreign_table_name} ({$fk->foreign_column_name});";
            })->toArray();

        return array_merge($foreignKeyStatementsWithPrefix, $foreignKeyStatementsWithoutPrefix);
    }
}

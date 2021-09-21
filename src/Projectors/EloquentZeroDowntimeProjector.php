<?php

namespace Mannum\ZeroDowntimeEventReplays\Projectors;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mannum\ZeroDowntimeEventReplays\ZeroDowntimeProjector;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

abstract class EloquentZeroDowntimeProjector extends Projector implements ZeroDowntimeProjector
{
    protected ?string $connection = null;

    abstract function models() : array;

    public function useConnection(string $connection): void
    {
        $this->connection = $connection;
        $this->configureConnections();
    }

    private function configureConnections(): void
    {
        foreach ($this->models() as $model) {
            if(!$model instanceof Model){
                throw new Exception("models in the models method should extend eloquents model class");
            }
            $defaultConnection = $model->getConnectionName() ?? config('database.default');
            $ghostConnection = $this->getGhostConnectionForModel($model);
            if(array_key_exists($ghostConnection, config('database.connections'))){
                return;
            }

            $connectionClone = config('database.connections.' . $defaultConnection);
            $connectionClone['prefix'] = 'replay_' . $this->connection . '_' . $connectionClone['prefix'];

            config(['database.connections.' . $ghostConnection => $connectionClone]);

            $newTable = $connectionClone['prefix'] . $model->getTable();

            //  todo: Deal with postgres auto increments
            DB::connection($model->getConnectionName())->statement("CREATE TABLE IF NOT EXISTS {$newTable} (LIKE {$model->getTable()} INCLUDING ALL);");
        }
    }

    public function getGhostConnectionForModel(Model $model) : string
    {
        $defaultConnection = $model->getConnectionName() ?? config('database.default');
        return 'replay_' . $this->connection . '_' . $defaultConnection;
    }


}

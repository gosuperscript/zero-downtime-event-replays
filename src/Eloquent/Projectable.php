<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Projectable
{
    /**
     * @param string|null $replayKey
     * @return Builder|static
     */
    public static function forProjection(?string $replayKey): Builder
    {
        /** @var Model $self */
        $self = new static();
        if (! $replayKey) {
            return $self->newQuery();
        }
        $defaultConnection = $self->getConnectionName() ?? config('database.default');
        $self->setConnection('replay_' . $replayKey . '_' . $defaultConnection);

        return $self->newQuery();
    }

    public static function newForProjection(?string $replayKey, array $attributes = []): static
    {
        /** @var Model $self */
        $self = new static($attributes);
        if (! $replayKey) {
            return $self;
        }
        $defaultConnection = $self->getConnectionName() ?? config('database.default');
        $self->setConnection('replay_' . $replayKey . '_' . $defaultConnection);

        return $self;
    }
}

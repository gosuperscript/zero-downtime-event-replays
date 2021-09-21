<?php

namespace Mannum\ZeroDowntimeEventReplays;

use Carbon\Carbon;

class ReplaySerializer
{
    public static function toArray(Replay $replay): array
    {
        return [
            'key' => $replay->key,
            'projectors' => $replay->projectors,
            'last_projected_event' => $replay->lastProjectedEvent,
            'active_play_id' => $replay->activePlayId,
            'plays' => self::playsToArray($replay->plays),
            'enabled_projections' => $replay->projectionsEnabled,
        ];
    }

    public static function fromArray(array $array): Replay
    {
        $replay = new Replay($array['key']);
        $replay->projectors = $array['projectors'];
        $replay->lastProjectedEvent = $array['last_projected_event'];
        $replay->activePlayId = $array['active_play_id'];
        $replay->projectionsEnabled = $array['enabled_projections'];
        $replay->plays = self::playsFromArray($array['plays']);

        return $replay;
    }

    private static function playsToArray(array $plays): array
    {
        return collect($plays)->map(function (Play $play) {
            return [
                'id' => $play->id,
                'started_at' => $play->started_at->toIso8601ZuluString(),
                'finished_at' => $play->finished_at?->toIso8601ZuluString(),
                'last_projected' => $play->lastProjected,
                'from' => $play->from,
            ];
        })->toArray();
    }

    private static function playsFromArray(mixed $plays): array
    {
        return collect($plays)->mapWithKeys(function (array $array) {
            $play = new Play($array['id']);
            $play->started_at = Carbon::parse($array['started_at']);
            if (array_key_exists('finished_at', $array) && $array['finished_at'] !== null) {
                $play->finished_at = Carbon::parse($array['finished_at']);
            }
            $play->lastProjected = $array['last_projected'];
            $play->from = $array['from'];

            return [$play->id => $play];
        })->toArray();
    }
}

<?php

namespace Mannum\ZeroDowntimeEventReplays\Repositories;

use Illuminate\Support\Facades\Redis;
use Mannum\ZeroDowntimeEventReplays\Replay;
use Mannum\ZeroDowntimeEventReplays\ReplaySerializer;

class RedisReplayRepository implements ReplayRepository
{
    public function __construct(public string $setKey = 'zero_downtime_replays')
    {
    }
    public function getReplayByKey(string $key): ?Replay
    {
        $json = Redis::hget($this->setKey, $key);
        if(!$json){
            return null;
        }
        return ReplaySerializer::fromArray(json_decode($json, true));
    }

    public function persist(Replay $replay)
    {
        $replayAsString = json_encode(ReplaySerializer::toArray($replay));
        Redis::hset($this->setKey, $replay->key, $replayAsString);
    }
}

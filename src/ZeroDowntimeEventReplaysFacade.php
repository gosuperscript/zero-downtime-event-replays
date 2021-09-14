<?php

namespace Mannum\ZeroDowntimeEventReplays;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mannum\ZeroDowntimeEventReplays\ZeroDowntimeEventReplays
 */
class ZeroDowntimeEventReplaysFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'zero-downtime-event-replays';
    }
}

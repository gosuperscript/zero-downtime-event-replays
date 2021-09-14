<?php

namespace Mannum\ZeroDowntimeEventReplays\Commands;

use Illuminate\Console\Command;

class ZeroDowntimeEventReplaysCommand extends Command
{
    public $signature = 'zero-downtime-event-replays';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}

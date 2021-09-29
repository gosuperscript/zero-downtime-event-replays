<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Models;

use Gosuperscript\ZeroDowntimeEventReplays\Eloquent\Projectable;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use Projectable;

    protected $guarded = [];
}

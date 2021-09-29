<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Models;

use Gosuperscript\ZeroDowntimeEventReplays\Eloquent\Projectable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use Projectable;

    protected $guarded = [];

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}

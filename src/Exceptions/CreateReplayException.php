<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Exceptions;

final class CreateReplayException extends \Exception
{
    public static function replayAlreadyExists(string $key): self
    {
        return new static("Replay with key {$key} already exists");
    }
}

<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests;

use Carbon\Carbon;
use Gosuperscript\ZeroDowntimeEventReplays\Replay;
use Gosuperscript\ZeroDowntimeEventReplays\ReplaySerializer;

class ReplaySerializerTest extends TestCase
{
    /** @dataProvider provider */
    public function test_it_parses_and_reconstructs_plays(Replay $replay)
    {
        $data = ReplaySerializer::toArray($replay);
        // Make sure array can be json encoded
        $data = json_decode(json_encode($data), true);

        $reconstructedReplay = ReplaySerializer::fromArray($data);
        self::assertEquals($replay, $reconstructedReplay);
    }

    public static function provider(): array
    {
        return [
            [self::replay()],
            [self::replayWithProjectors()],
            [self::startedReplay()],
            [self::replayInProgress()],
            [self::finishedReplay()],
            [self::twoReplays()],
            [self::enabledProjections()],
        ];
    }

    private static function replay(): Replay
    {
        return new Replay('foo');
    }

    private static function replayWithProjectors(): Replay
    {
        $replay = self::replay();
        $replay->addProjector('foobar');
        $replay->addProjector('baz');

        return $replay;
    }

    private static function startedReplay(): Replay
    {
        $replay = self::replayWithProjectors();
        $replay->started(0, Carbon::parse('2021-01-01 10:00:00'));

        return $replay;
    }

    private static function replayInProgress(): Replay
    {
        $replay = self::startedReplay();
        $replay->setLastProjectedEventNumber(10);

        return $replay;
    }

    private static function finishedReplay(): Replay
    {
        $replay = self::replayInProgress();
        $replay->finished(Carbon::parse('2021-01-01 12:00:00'));

        return $replay;
    }

    private static function twoReplays(): Replay
    {
        $replay = self::finishedReplay();
        $replay->started(100, Carbon::parse('2021-01-02 14:00:00'));
        $replay->finished(Carbon::parse('2021-01-02 16:00:00'));

        return $replay;
    }

    private static function enabledProjections(): Replay
    {
        $replay = self::replay();
        $replay->enableProjections();

        return $replay;
    }
}

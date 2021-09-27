<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests;

use Carbon\Carbon;
use Gosuperscript\ZeroDowntimeEventReplays\Replay;
use Gosuperscript\ZeroDowntimeEventReplays\ReplaySerializer;

class ReplaySerializerTest extends TestCase
{
    /** @test
     * @dataProvider provider
     */
    public function it_parses_and_reconstructs_plays(Replay $replay)
    {
        $data = ReplaySerializer::toArray($replay);
        // Make sure array can be json encoded
        $data = json_decode(json_encode($data), true);

        $reconstructedReplay = ReplaySerializer::fromArray($data);
        $this->assertEquals($replay, $reconstructedReplay);
    }

    public function provider(): array
    {
        return [
            [$this->replay()],
            [$this->replayWithProjectors()],
            [$this->startedReplay()],
            [$this->replayInProgress()],
            [$this->finishedReplay()],
            [$this->twoReplays()],
            [$this->enabledProjections()],
        ];
    }

    private function replay(): Replay
    {
        return new Replay('foo');
    }

    private function replayWithProjectors(): Replay
    {
        $replay = $this->replay();
        $replay->addProjector('foobar');
        $replay->addProjector('baz');

        return $replay;
    }

    private function startedReplay(): Replay
    {
        $replay = $this->replayWithProjectors();
        $replay->started(0, Carbon::parse('2021-01-01 10:00:00'));

        return $replay;
    }

    private function replayInProgress(): Replay
    {
        $replay = $this->startedReplay();
        $replay->setLastProjectedEventNumber(10);

        return $replay;
    }

    private function finishedReplay(): Replay
    {
        $replay = $this->replayInProgress();
        $replay->finished(Carbon::parse('2021-01-01 12:00:00'));

        return $replay;
    }

    private function twoReplays(): Replay
    {
        $replay = $this->finishedReplay();
        $replay->started(100, Carbon::parse('2021-01-02 14:00:00'));
        $replay->finished(Carbon::parse('2021-01-02 16:00:00'));

        return $replay;
    }

    private function enabledProjections(): Replay
    {
        $replay = $this->replay();
        $replay->enableProjections();

        return $replay;
    }
}

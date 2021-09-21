<?php

namespace Mannum\ZeroDowntimeEventReplays\Tests;

use Mannum\ZeroDowntimeEventReplays\Exceptions\CreateReplayException;
use Mannum\ZeroDowntimeEventReplays\ReplayManager;
use Mannum\ZeroDowntimeEventReplays\Tests\Fakes\FakeProjectionist;

class ReplayManagerTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_replay_already_exists_on_start()
    {
        $manager = new ReplayManager(new InMemoryReplayRepository(), new FakeProjectionist());
        $manager->createReplay('foo', []);

        $thrown = false;
        try {
            $manager->createReplay('foo', []);
        } catch (CreateReplayException $e)
        {
            $thrown = true;
        }
        $this->assertTrue($thrown, "Exception StartReplayException expected but not thrown");
    }

    /** @test */
    public function it_validates_if_projector_is_configured()
    {
        $projectionist = new FakeProjectionist();
        $repo = new InMemoryReplayRepository();
        $manager = new ReplayManager($repo, $projectionist);

        $this->expectException(\Exception::class);
        $manager->createReplay('foo', [
            'ThisProjectorIsNotRegistered'
        ]);

        $this->assertNull($repo->getReplayByKey('foo'));
    }

    /** @test */
    public function it_persists_replay_with_valid_projectors()
    {
        $projectionist = new FakeProjectionist();
        $projectionist->addProjector('RegisteredProjector');
        $repo = new InMemoryReplayRepository();
        $manager = new ReplayManager($repo, $projectionist);

        $manager->createReplay('foo', [
            'RegisteredProjector'
        ]);

        $replay = $repo->getReplayByKey('foo');
        $this->assertNotNull($replay);
        $this->assertEquals(['RegisteredProjector'], $replay->projectors);
    }

    /** @test */
    public function it_starts_a_replay()
    {
        $repo = new InMemoryReplayRepository();
        $manager = new ReplayManager($repo, new FakeProjectionist());

        $manager->createReplay('foo', []);
        $manager->startReplay('foo');

    }
}

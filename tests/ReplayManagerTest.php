<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests;

use Gosuperscript\ZeroDowntimeEventReplays\Exceptions\CreateReplayException;
use Gosuperscript\ZeroDowntimeEventReplays\ReplayManager;
use Gosuperscript\ZeroDowntimeEventReplays\Repositories\InMemoryReplayRepository;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\Fakes\FakeProjectionist;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\Fakes\FakeProjector;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\Fakes\FakeStoredEventRepository;

class ReplayManagerTest extends TestCase
{
    public function test_it_throws_exception_when_replay_already_exists_on_start()
    {
        $manager = new ReplayManager(new InMemoryReplayRepository(), new FakeProjectionist(), new FakeStoredEventRepository());
        $manager->createReplay('foo', []);

        $thrown = false;

        try {
            $manager->createReplay('foo', []);
        } catch (CreateReplayException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown, "Exception StartReplayException expected but not thrown");
    }

    public function test_it_validates_if_projector_is_configured()
    {
        $projectionist = new FakeProjectionist();
        $repo = new InMemoryReplayRepository();
        $manager = new ReplayManager($repo, $projectionist, new FakeStoredEventRepository());

        $this->expectException(\Exception::class);
        $manager->createReplay('foo', [
            'ThisProjectorIsNotRegistered',
        ]);

        $this->assertNull($repo->getReplayByKey('foo'));
    }

    public function test_it_persists_replay_with_valid_projectors()
    {
        $projectionist = new FakeProjectionist();
        $projectionist->addProjector('RegisteredProjector');
        $repo = new InMemoryReplayRepository();
        $manager = new ReplayManager($repo, $projectionist, new FakeStoredEventRepository());

        $manager->createReplay('foo', [
            'RegisteredProjector',
        ]);

        $replay = $repo->getReplayByKey('foo');
        $this->assertNotNull($replay);
        $this->assertEquals(['RegisteredProjector'], $replay->projectors);
    }

    public function test_it_starts_a_replay()
    {
        $repo = new InMemoryReplayRepository();
        $projectionist = new FakeProjectionist();
        $manager = new ReplayManager($repo, $projectionist, new FakeStoredEventRepository());

        $manager->createReplay('foo', []);
        $manager->startReplay('foo');

        $replay = $repo->getReplayByKey('foo');
        $this->assertCount(1, $replay->plays);

        $this->assertEquals(0, $projectionist->replays[0]['startingFrom']);
    }

    public function test_it_starts_a_replay_from_last_projected_event()
    {
        $repo = new InMemoryReplayRepository();
        $projectionist = new FakeProjectionist();
        $manager = new ReplayManager($repo, $projectionist, new FakeStoredEventRepository());

        $manager->createReplay('foo', []);
        $replay = $repo->getReplayByKey('foo');
        $replay->lastProjectedEvent = 100;
        $repo->persist($replay);

        $manager->startReplay('foo');

        $replay = $repo->getReplayByKey('foo');
        $this->assertCount(1, $replay->plays);

        $this->assertEquals(101, $projectionist->replays[0]['startingFrom']);
    }

    public function test_it_gets_replay_lag()
    {
        $repo = new InMemoryReplayRepository();
        $projectionist = new FakeProjectionist();
        $storedEventRepo = new FakeStoredEventRepository();
        $storedEventRepo->setCountStartingFrom(101, 44);

        $manager = new ReplayManager($repo, $projectionist, $storedEventRepo);

        $manager->createReplay('foo', []);
        $replay = $repo->getReplayByKey('foo');
        $replay->lastProjectedEvent = 100;
        $repo->persist($replay);

        $this->assertEquals(44, $manager->getReplayLag('foo'));
    }

    public function test_it_enables_projections_when_lag_is_0()
    {
        $repo = new InMemoryReplayRepository();
        $projectionist = new FakeProjectionist();
        $storedEventRepo = new FakeStoredEventRepository();

        $manager = new ReplayManager($repo, $projectionist, $storedEventRepo);

        $manager->createReplay('foo', []);

        $manager->startProjectingToReplay('foo');

        $replay = $repo->getReplayByKey('foo');
        $this->assertTrue($replay->projectionsEnabled);
    }

    public function test_it_does_not_enable_projections_when_lag_is_not_0()
    {
        $repo = new InMemoryReplayRepository();
        $projectionist = new FakeProjectionist();
        $storedEventRepo = new FakeStoredEventRepository();
        $storedEventRepo->setCountStartingFrom(1, 10);

        $manager = new ReplayManager($repo, $projectionist, $storedEventRepo);

        $manager->createReplay('foo', []);

        $thrown = false;

        try {
            $manager->startProjectingToReplay('foo');
        } catch (\Exception $exception) {
            $thrown = true;
        }

        $replay = $repo->getReplayByKey('foo');
        $this->assertFalse($replay->projectionsEnabled);
        $this->assertTrue($thrown, "exception not thrown");
    }

    public function test_it_calls_projectors_to_put_replay_live()
    {
        $projector = new FakeProjector();

        $projectionist = new FakeProjectionist();
        $projectionist->addProjector('RegisteredProjector', $projector);
        $repo = new InMemoryReplayRepository();
        $manager = new ReplayManager($repo, $projectionist, new FakeStoredEventRepository());

        $manager->createReplay('foo', [
            'RegisteredProjector',
        ]);

        $manager->putReplayLive('foo');

        $this->assertTrue($projector->hasBeenPutLive());
    }

    public function test_it_can_remove_replays()
    {
        $projector = new FakeProjector();

        $projectionist = new FakeProjectionist();
        $projectionist->addProjector('RegisteredProjector', $projector);
        $repo = new InMemoryReplayRepository();
        $manager = new ReplayManager($repo, $projectionist, new FakeStoredEventRepository());

        $manager->createReplay('foo', [
            'RegisteredProjector',
        ]);

        $manager->removeReplay('foo');

        $this->assertTrue($projector->hasBeenRemoved());

        $this->assertNull($repo->getReplayByKey('foo'));
    }
}

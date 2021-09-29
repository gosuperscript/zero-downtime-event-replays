<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests\Projectors;

use Gosuperscript\ZeroDowntimeEventReplays\Tests\TestCase;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Models\Comment;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Models\Post;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Projectors\PostProjector;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

class EloquentZeroDowntimeProjectorTest extends TestCase
{
    /** @test */
    public function it_creates_new_tables_with_the_right_sequences_and_foreign_keys()
    {
        $projector = new PostProjector();
        $projector->forReplay()->useConnection('foo');

        // Connections should now be configured
        $this->assertTrue(Schema::hasTable('replay_foo_posts'));
        $this->assertTrue(Schema::hasTable('replay_foo_comments'));

        // test foreign key on comments work
        $thrown = false;

        try {
            Comment::forProjection('foo')->create(['post_id' => Uuid::uuid4()->toString(), 'content' => 'foo']);
        } catch (QueryException $e) {
            $this->assertStringContainsString("Foreign key violation", $e->getMessage());
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $comment = Comment::create(['post_id' => Post::create(['id' => Uuid::uuid4(), 'title' => 'foo'])->id, 'content' => 'foo']);

        // ID should be one, since other created comment should have another sequence
        $this->assertEquals(1, $comment->id);
    }

    /** @test */
    public function it_drops_table_and_sequences_on_removal()
    {
        $projector = new PostProjector();
        $projector->forReplay()->useConnection('foobar');

        $this->assertTrue(Schema::hasTable('replay_foobar_posts'));
        $this->assertTrue(Schema::hasTable('replay_foobar_comments'));

        $projector->removeConnection();

        $this->assertFalse(Schema::hasTable('replay_foobar_posts'));
        $this->assertFalse(Schema::hasTable('replay_foobar_posts'));
    }
}

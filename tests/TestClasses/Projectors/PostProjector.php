<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Projectors;

use Gosuperscript\ZeroDowntimeEventReplays\Projectors\EloquentZeroDowntimeProjector;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Events\CommentPlaced;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Events\PostPublished;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Models\Comment;
use Gosuperscript\ZeroDowntimeEventReplays\Tests\TestClasses\Models\Post;

class PostProjector extends EloquentZeroDowntimeProjector
{
    public function models(): array
    {
        return [
            new Post(),
            new Comment(),
        ];
    }

    public function resetState()
    {
        Post::forProjection($this->connection)->truncate();
        Comment::forProjection($this->connection)->truncate();
    }

    public function onPostPublished(PostPublished $postPublished)
    {
        Post::forProjection($this->connection)->create([
            'id' => $postPublished->aggregateRootUuid(),
            'title' => $postPublished->title,
        ]);
    }

    public function onCommentPlaced(CommentPlaced $commentPlaced)
    {
        /** @var Post $post */
        $post = Post::find($commentPlaced->aggregateRootUuid());
        $post->comments()->create([
            'content' => $commentPlaced->content,
        ]);
    }
}

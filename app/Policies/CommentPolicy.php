<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /** Chủ bình luận hoặc admin được xóa. */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->hasRole('admin');
    }
}

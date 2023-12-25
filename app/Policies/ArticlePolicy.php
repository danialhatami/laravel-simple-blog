<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use App\Enums\PermissionEnum as Permission;
use App\Enums\ArticleStatusEnum;
use Illuminate\Auth\Access\Response;

class ArticlePolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo(Permission::CREATE_ARTICLES)
            ? Response::allow()
            : Response::deny('You do not have permission to create articles');
    }

    public function update(User $user, Article $article): Response
    {
        return $user->hasPermissionTo(Permission::UPDATES_ARTICLES) && $user->id === $article->author_id
            ? Response::allow()
            : Response::deny('You do not have permission to update articles');
    }

    public function delete(User $user, Article $article): Response
    {
        return $user->hasPermissionTo(Permission::DELETE_ARTICLES) && is_null($article->deleted_at)
            ? Response::allow()
            : Response::deny('You do not have permission to delete articles');
    }

    public function publish(User $user, Article $article): Response
    {
        return $user->hasPermissionTo(Permission::PUBLISH_ARTICLES) && $article->status === ArticleStatusEnum::DRAFT
            ? Response::allow()
            : Response::deny('You do not have permission to publish articles');
    }

    public function restore(User $user, Article $article): Response
    {
        return $user->hasPermissionTo(Permission::RESTORE_ARTICLES) && !is_null($article->deleted_at)
            ? Response::allow()
            : Response::deny('You do not have permission to restore articles');
    }
}

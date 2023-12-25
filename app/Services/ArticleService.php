<?php

namespace App\Services;

use Cache;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Article;
use App\Enums\PermissionEnum;
use App\Enums\ArticleStatusEnum;
use Illuminate\Support\Facades\DB;
use App\Exceptions\DatabaseException;
use App\DataTransferObjects\ArticleData;
use App\Exceptions\ArticleNotTrashedException;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exceptions\UnauthorizedActionException;
use App\Exceptions\ArticleAlreadyDeletedException;
use App\Exceptions\ArticleAlreadyPublishedException;

class ArticleService
{
    public function createArticle(ArticleData $articleData): Article
    {
        if (!$articleData->author->hasPermissionTo(PermissionEnum::CREATE_ARTICLES)) {
            throw new UnauthorizedActionException('You do not have permission to create articles');
        }
        return Article::create($articleData->toArray());
    }

    public function getPublishedAndPaginatedArticles(): LengthAwarePaginator
    {
        $page = request('page', 1);
        $cacheKey = 'published_articles_page_' . $page;

        return Cache::tags(['articles'])->remember($cacheKey, 60, function () {
            return Article::where('status', ArticleStatusEnum::PUBLISHED)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereNull('deleted_at')
                ->orderBy('published_at', 'desc')
                ->paginate(config('pagination.items_per_page'));
        });
    }

    public function publishArticle(Article $article, User $user, Carbon $publishDate = null): Article
    {
        if (!$user->hasPermissionTo(PermissionEnum::PUBLISH_ARTICLES)) {
            throw new UnauthorizedActionException('You do not have permission to publish articles');
        }

        if ($article->status === ArticleStatusEnum::PUBLISHED) {
            throw new ArticleAlreadyPublishedException('This article is already published');
        }
        $article->publish($user, $publishDate);
        Cache::tags(['articles'])->flush();
        return $article;
    }

    public function deleteArticle(Article $article, User $user)
    {
        if (!$user->hasPermissionTo(PermissionEnum::DELETE_ARTICLES)) {
            throw new UnauthorizedActionException('You do not have permission to delete articles');
        }

        if ($article->trashed()) {
            throw new ArticleAlreadyDeletedException('This article is already deleted.');
        }
        try {
            DB::transaction(function () use ($article) {
                $article->delete();
                $article->status = ArticleStatusEnum::TRASHED;
                $article->save();
            });
        } catch (\Throwable $e) {
            throw new DatabaseException('There was an error in deleting the article.');
        }
        return $article;
    }

    public function restoreArticle(Article $article, User $user)
    {
        if (!$user->hasPermissionTo(PermissionEnum::RESTORE_ARTICLES)) {
            throw new UnauthorizedActionException('You do not have permission to restore articles');
        }

        if (!$article->trashed()) {
            throw new ArticleNotTrashedException('Cannot restore an article that is not trashed.');
        }

        try {
            DB::transaction(function () use ($article) {
                $article->restore();
                $article->status = ArticleStatusEnum::DRAFT;
                $article->save();
            });
        } catch (\Throwable $e) {
            throw new DatabaseException('There was an error in restoring the article');
        }

        return $article;
    }

    public function updateArticle(Article $article, User $user, ArticleData $articleData)
    {
        if (!$user->hasPermissionTo(PermissionEnum::UPDATES_ARTICLES)) {
            throw new UnauthorizedActionException('You do not have permission to update articles');
        }
        $article->update($articleData->toArray());
        return $article;
    }
}

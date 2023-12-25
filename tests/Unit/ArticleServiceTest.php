<?php

namespace Tests\Unit;

use DB;
use Cache;
use App\Models\User;
use App\Enums\RoleEnum;
use Tests\TestCase;
use App\Models\Article;
use App\Services\ArticleService;
use App\Enums\ArticleStatusEnum;
use App\Exceptions\DatabaseException;
use App\DataTransferObjects\ArticleData;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\WithFaker;
use App\Exceptions\ArticleNotTrashedException;
use Database\Seeders\RolesAndPermissionsSeeder;
use App\Exceptions\UnauthorizedActionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\ArticleAlreadyPublishedException;

class ArticleServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private User $firstAuthor;
    private User $secondAuthor;
    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->articleService = app(ArticleService::class);
        $this->firstAuthor = User::factory()->create()->assignRole(RoleEnum::AUTHOR);
        $this->secondAuthor = User::factory()->create()->assignRole(RoleEnum::AUTHOR);
        $this->admin = User::factory()->create()->assignRole(RoleEnum::ADMIN);
        $this->user = User::factory()->createOne();
    }

    public function test_author_creates_an_article_successfully()
    {
        $articleData = new ArticleData(
            title: $this->faker->realText(10),
            slug: $this->faker->slug,
            content: $this->faker->realText,
            author: $this->secondAuthor
        );

        $article = $this->articleService->createArticle($articleData);

        $this->assertModelExists($article);
        $this->assertEquals($articleData->title, $article->title);
    }

    public function test_author_does_not_have_permission_to_create_article()
    {
        $articleData = new ArticleData(
            title: $this->faker->realText(10),
            slug: $this->faker->slug,
            content: $this->faker->realText,
            author: $this->admin
        );
        $this->expectException(UnauthorizedActionException::class);
        $this->articleService->createArticle($articleData);
    }

    public function test_admin_publishes_an_article_successfully()
    {
        $article = Article::factory()->create(['status' => ArticleStatusEnum::DRAFT]);

        $publishedArticle = $this->articleService->publishArticle($article, $this->admin);

        $this->assertModelExists($publishedArticle);
        $this->assertEquals(ArticleStatusEnum::PUBLISHED, $publishedArticle->status);
    }

    public function test_user_does_not_have_permission_to_publish_article()
    {
        $article = Article::factory()->create(['status' => ArticleStatusEnum::DRAFT]);

        $this->expectException(UnauthorizedActionException::class);
        $this->articleService->publishArticle($article, $this->user);
    }

    public function test_admin_deletes_an_article_successfully()
    {
        $article = Article::factory()->create();

        $deletedArticle = $this->articleService->deleteArticle($article, $this->admin);

        $this->assertSoftDeleted($deletedArticle);
    }

    public function test_user_does_not_have_permission_to_delete_article()
    {
        $article = Article::factory()->create();

        $this->expectException(UnauthorizedActionException::class);
        $this->articleService->deleteArticle($article, $this->user);

        $this->expectException(UnauthorizedActionException::class);
        $this->articleService->deleteArticle($article, $this->firstAuthor);
    }

    public function test_admin_restores_a_trashed_article_successfully()
    {
        $article = Article::factory()->create(['deleted_at' => now()]);

        $restoredArticle = $this->articleService->restoreArticle($article, $this->admin);

        $this->assertModelExists($restoredArticle);
        $this->assertEquals(ArticleStatusEnum::DRAFT, $restoredArticle->status);
    }

    public function test_user_does_not_have_permission_to_restore_article()
    {
        $article = Article::factory()->create(['deleted_at' => now()]);

        $this->expectException(UnauthorizedActionException::class);
        $this->articleService->restoreArticle($article, $this->user);

        $this->expectException(UnauthorizedActionException::class);
        $this->articleService->restoreArticle($article, $this->firstAuthor);
    }

    public function test_user_does_not_have_permission_to_update_article()
    {
        $article = Article::factory()->create();
        $newData = new ArticleData(
            title: $this->faker->realText(10),
            slug: $this->faker->slug,
            content: $this->faker->realText,
            author: $this->user
        );

        $this->expectException(UnauthorizedActionException::class);
        $this->articleService->updateArticle($article, $this->user, $newData);

        $this->expectException(UnauthorizedActionException::class);
        $this->articleService->updateArticle($article, $this->admin, $newData);
    }

    public function test_publishing_already_published_article_throws_exception()
    {
        $article = Article::factory()->create(['status' => ArticleStatusEnum::PUBLISHED]);

        $this->expectException(ArticleAlreadyPublishedException::class);
        $this->articleService->publishArticle($article, $this->admin);
    }

    public function test_publishing_article_clears_cache()
    {
        $article = Article::factory()->create(['status' => ArticleStatusEnum::DRAFT]);

        Cache::shouldReceive('tags')
            ->with(['articles'])
            ->andReturnSelf()
            ->shouldReceive('flush')
            ->once();

        $this->articleService->publishArticle($article, $this->admin);
    }

    public function test_restoring_not_trashed_article_throws_exception()
    {
        $article = Article::factory()->create();

        $this->expectException(ArticleNotTrashedException::class);
        $this->articleService->restoreArticle($article, $this->admin);
    }

    public function test_database_error_on_deleting_article_throws_exception()
    {
        $article = Article::factory()->create();

        DB::shouldReceive('transaction')
            ->andThrow(new \Exception('Database Error'));

        $this->expectException(DatabaseException::class);
        $this->articleService->deleteArticle($article, $this->admin);
    }

    public function test_database_error_on_restoring_article_throws_exception()
    {
        $article = Article::factory()->create(['deleted_at' => now()]);

        DB::shouldReceive('transaction')
            ->andThrow(new \Exception('Database Error'));

        $this->expectException(DatabaseException::class);
        $this->articleService->restoreArticle($article, $this->admin);
    }

    public function test_get_published_and_paginated_articles_with_caching()
    {
        Article::factory(15)->create(
            [
                'status' => ArticleStatusEnum::PUBLISHED,
                'deleted_at' => null,
                'published_at' => now()
            ]);

        Cache::shouldReceive('tags')
            ->with(['articles'])
            ->andReturnSelf()
            ->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $articles = $this->articleService->getPublishedAndPaginatedArticles();

        $this->assertNotEmpty($articles);
        $this->assertCount(config('pagination.items_per_page'), $articles);
    }

    public function test_full_lifecycle_of_an_article()
    {
        $articleData = new ArticleData(
            title: $this->faker->realText(10),
            slug: $this->faker->slug,
            content: $this->faker->realText,
            author: $this->firstAuthor
        );

        $createdArticle = $this->articleService->createArticle($articleData);
        $this->articleService->publishArticle($createdArticle, $this->admin);
        $this->articleService->deleteArticle($createdArticle, $this->admin);
        $restoredArticle = $this->articleService->restoreArticle($createdArticle, $this->admin);

        $this->assertEquals(ArticleStatusEnum::DRAFT, $restoredArticle->status);
    }

    public function test_get_published_and_paginated_articles_excludes_future_published_articles()
    {
        Article::factory()->create([
            'status' => ArticleStatusEnum::PUBLISHED,
            'published_at' => now()->addDays(10),
            'deleted_at' => null,
        ]);

        Article::factory()->create([
            'status' => ArticleStatusEnum::PUBLISHED,
            'published_at' => now()->subDays(10),
            'deleted_at' => null,
        ]);

        $articles = $this->articleService->getPublishedAndPaginatedArticles();

        $this->assertNotEmpty($articles);
        $this->assertCount(1, $articles);
        $this->assertEquals(now()->subDays(10)->format('Y-m-d'), $articles->first()->published_at->format('Y-m-d'));
    }
}

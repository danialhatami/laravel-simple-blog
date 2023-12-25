<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Filament\Panel;
use App\Models\Article;
use App\Enums\PermissionEnum;
use App\Policies\ArticlePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Article::class => ArticlePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('access-panel', function ($user) {
            return $user->hasPermissionTo(PermissionEnum::ACCESS_PANEL);
        });
    }
}

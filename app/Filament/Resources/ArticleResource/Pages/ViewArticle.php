<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Models\Article;
use App\Enums\PermissionEnum;
use App\Services\ArticleService;
use App\Enums\ArticleStatusEnum;
use Filament\Notifications\Notification;
use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArticle extends ViewRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\RestoreAction::make(),
            Actions\Action::make('publish')
                ->action(function (Article $record) {
                    try {
                        $articleService = app(ArticleService::class);
                        $articleService->publishArticle($record, auth()->user());
                        Notification::make()
                            ->title('Article Published Successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    }

                })
                ->requiresConfirmation()
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn (Article $record): bool => auth()->user()->hasPermissionTo(PermissionEnum::PUBLISH_ARTICLES) and !$record->trashed() and $record->status !== ArticleStatusEnum::PUBLISHED),
        ];
    }
}

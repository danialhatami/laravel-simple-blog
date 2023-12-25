<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Services\ArticleService;
use Illuminate\Database\Eloquent\Model;
use App\DataTransferObjects\ArticleData;
use App\Filament\Resources\ArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author'] = auth()->user();
        return $data;
    }

    public function handleRecordCreation(array $data): Model
    {
        $articleData = new ArticleData(
            title: $data['title'],
            slug: $data['slug'],
            content: $data['content'],
            author: $data['author']
        );
        return app(ArticleService::class)->createArticle($articleData);
    }
}

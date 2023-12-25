<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Enums\PermissionEnum;
use App\Services\ArticleService;
use App\Enums\ArticleStatusEnum;
use Filament\Notifications\Notification;
use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(512)
                                ->live()
                                ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),
                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->maxLength(512),
                        ]),
                        Forms\Components\RichEditor::make('content')
                            ->required()
                    ])->compact()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ArticleStatusEnum $state): string => match ($state) {
                        ArticleStatusEnum::DRAFT => 'gray',
                        ArticleStatusEnum::PUBLISHED => 'success',
                        ArticleStatusEnum::TRASHED => 'danger',
                    }),
                Tables\Columns\TextColumn::make('author.name'),
                Tables\Columns\TextColumn::make('published_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->toggleable()
            ])
            ->filters([
                Tables\Filters\Filter::make('Waiting for Publish')
                    ->query(fn (Builder $query) => $query->where('status',  ArticleStatusEnum::DRAFT)),
                Tables\Filters\Filter::make('Trashed')
                    ->query(fn (Builder $query) => $query->onlyTrashed()),
                Tables\Filters\Filter::make('By Myself')
                    ->query(fn (Builder $query) => $query->where('author_id', auth()->user()->id))
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->action(function (Article $record) {
                        try {
                            $articleService = app(ArticleService::class);
                            $articleService->restoreArticle($record, auth()->user());
                            Notification::make()
                                ->title('Article Restored Successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        }

                    }),
                Tables\Actions\DeleteAction::make()
                    ->action(function (Article $record) {
                        try {
                            $articleService = app(ArticleService::class);
                            $articleService->deleteArticle($record, auth()->user());
                            Notification::make()
                                ->title('Article Deleted Successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        }

                    }),
                Tables\Actions\Action::make('publish')
                    ->action(function (Article $record, array $data) {
                        try {
                            $articleService = app(ArticleService::class);
                            $publishDate = Carbon::createFromFormat('Y-m-d', $data['publish_date']);

                            $articleService->publishArticle($record, auth()->user(), $publishDate);
                            if ($publishDate->isToday()) {
                                Notification::make()
                                    ->title('Article Published Successfully')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Article Scheduled for Future Publication')
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->form([
                        Forms\Components\DatePicker::make('publish_date')
                            ->label('Publish Date')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Publish Article')
                    ->modalButton('Publish')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Article $record): bool => auth()->user()->hasPermissionTo(PermissionEnum::PUBLISH_ARTICLES) && !$record->trashed() && $record->status !== ArticleStatusEnum::PUBLISHED)
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'view' => Pages\ViewArticle::route('/{record}'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

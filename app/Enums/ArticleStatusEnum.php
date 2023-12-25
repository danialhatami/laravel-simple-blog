<?php

namespace App\Enums;

enum ArticleStatusEnum: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case TRASHED = 'TRASHED';

    public static function values(): array
    {
        return collect(self::cases())->map(fn ($item) => $item->value)->toArray();
    }

}

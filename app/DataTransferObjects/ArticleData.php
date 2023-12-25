<?php

namespace App\DataTransferObjects;

use App\Models\User;
use Spatie\LaravelData\Data;

class ArticleData extends Data
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $content,
        public ?User   $author
    )
    {
    }
}

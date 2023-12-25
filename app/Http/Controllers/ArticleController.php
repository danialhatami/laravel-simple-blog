<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Services\ArticleService;

class ArticleController extends Controller
{
    public function __construct(private readonly ArticleService $articleService)
    {
    }

    public function index(): View
    {
        $articles = $this->articleService->getPublishedAndPaginatedArticles();
        return view('welcome', compact('articles'));
    }
}

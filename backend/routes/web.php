<?php

use App\Http\Controllers\BlogWebController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Api\Blog\BlogController;
use App\Http\Middleware\BlogRedirectMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware([BlogRedirectMiddleware::class])->group(function () {
    Route::get('/blog', [BlogWebController::class, 'index']);
    Route::get('/blog/search', [BlogWebController::class, 'search']);
    Route::get('/blog/feed', [BlogController::class, 'feed']);
    Route::get('/feed', [BlogController::class, 'feed']);
    Route::get('/blog/category/{slug}', [BlogWebController::class, 'category']);
    Route::get('/blog/tag/{slug}', [BlogWebController::class, 'tag']);
    Route::get('/blog/author/{slug}', [BlogWebController::class, 'author']);
    Route::get('/blog/preview/{token}', [BlogWebController::class, 'preview']);
    Route::get('/blog/{slug}', [BlogWebController::class, 'show']);
});

Route::get('/sitemap.xml', [SitemapController::class, 'xml']);
Route::get('/sitemap-index.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemap-posts.xml', [SitemapController::class, 'posts']);
Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories']);
Route::get('/sitemap-blog.xml', [SitemapController::class, 'blog']);
Route::get('/sitemap-tours.xml', [SitemapController::class, 'tours']);
Route::get('/robots.txt', [BlogWebController::class, 'robots']);

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
        'message' => 'Posheh API is running. Build the frontend or open /api/v1/plans',
    ]);
});

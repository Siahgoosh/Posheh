<?php

use App\Http\Controllers\BlogWebController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/blog', [BlogWebController::class, 'index']);
Route::get('/blog/category/{slug}', [BlogWebController::class, 'category']);
Route::get('/blog/{slug}', [BlogWebController::class, 'show']);

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemap-blog.xml', [SitemapController::class, 'blog']);
Route::get('/robots.txt', [BlogWebController::class, 'robots']);

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
        'message' => 'Posheh API is running. Build the frontend or open /api/v1/plans',
    ]);
});

<?php

use App\Http\Controllers\SeoRenderController;
use Illuminate\Support\Facades\Route;

// HTML pages for search-engine crawlers (nginx sends bots here via PHP)
Route::get('/', [SeoRenderController::class, 'home']);
Route::get('/blog', [SeoRenderController::class, 'blogIndex']);
Route::get('/blog/category/{category}', [SeoRenderController::class, 'blogCategory']);
Route::get('/blog/{slug}', [SeoRenderController::class, 'blogPost'])->where('slug', '[a-zA-Z0-9\-_%]+');

<?php

use Illuminate\Support\Facades\Route;

Route::get('/docs', fn () => response()->file(base_path('docs/swagger.html')));

Route::get('/docs/openapi.json', fn () => response()
    ->file(base_path('docs/openapi.json'), ['Content-Type' => 'application/json']));

Route::get('/docs/postman_collection.json', fn () => response()
    ->file(base_path('docs/postman_collection.json'), ['Content-Type' => 'application/json']));

Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api|docs).*$');

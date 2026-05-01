<?php

use Illuminate\Support\Facades\Route;

Route::get('/docs', fn () => response(
    file_get_contents(base_path('docs/swagger.html')),
    200,
    ['Content-Type' => 'text/html'],
));

Route::get('/docs/openapi.json', fn () => response(
    file_get_contents(base_path('docs/openapi.json')),
    200,
    ['Content-Type' => 'application/json'],
));

Route::get('/docs/postman_collection.json', fn () => response(
    file_get_contents(base_path('docs/postman_collection.json')),
    200,
    ['Content-Type' => 'application/json'],
));

Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api|docs).*$');

<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

/*Route::get('/', function () {
    return view('welcome');
});*/
Route::get('/catalog', [\App\Http\Controllers\ProductController::class, 'index']);
Route::post('/create-order', [\App\Http\Controllers\OrderController::class, 'create'])->withoutMiddleware([
    VerifyCsrfToken::class,
]);
Route::post('/approve-order',  [\App\Http\Controllers\OrderController::class, 'approved'])->withoutMiddleware([
    VerifyCsrfToken::class,
]);;

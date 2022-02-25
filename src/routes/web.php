<?php

use Benbraide\PathRouting\PathRoutingController;
use Illuminate\Support\Facades\Route;

Route::get('/register', [PathRoutingController::class, 'handle'])->middleware('web')->name('register');
Route::get('/login', [PathRoutingController::class, 'handle'])->middleware('web')->name('login');

Route::fallback([PathRoutingController::class, 'handle'])->middleware('web');

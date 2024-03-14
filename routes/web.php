<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('dd', function () {
    \Illuminate\Support\Facades\Artisan::call('websockets:serve');

    return view('welcome');
});

Route::get('ss', function () {
    event(new \App\Events\MessageSent());

    return null;
});

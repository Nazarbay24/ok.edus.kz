<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => '{locale}', 'where' => ['locale' => '[a-zA-Z]{2}'], 'middleware' => 'set.locale'], function()
{
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);

    Route::get('refresh-token', [\App\Http\Controllers\AuthController::class, 'refreshToken'])->middleware([
        'auth:sanctum',
        'ability:refresh',
    ]);

    Route::get('ddd', function (Request $request) {
        \Illuminate\Support\Facades\Artisan::call('websockets:serve');

        return response()->json(['message' => 'websocket up']);
    });

    Route::group(['middleware' => ['auth:sanctum', 'ability:access']], function ()
    {
        Route::get('logout', [\App\Http\Controllers\AuthController::class, 'logout']);

        Route::get('schedule', [\App\Http\Controllers\DiaryController::class, 'schedule']);

        Route::get('predmets-list', [\App\Http\Controllers\GradesController::class, 'predmetsList']);

        Route::get('jurnal-today', [\App\Http\Controllers\DiaryController::class, 'jurnalToday']);

        Route::post('jurnal-grades', [\App\Http\Controllers\GradesController::class, 'jurnalGrades']);

        Route::get('chetvert-grades', [\App\Http\Controllers\GradesController::class, 'chetvertGrades']);

        Route::post('criterial-grades', [\App\Http\Controllers\GradesController::class, 'criterialGrades']);

        Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'profile']);

        Route::get('class-info', [\App\Http\Controllers\ProfileController::class, 'classInfo']);

        Route::get('teachers-list', [\App\Http\Controllers\ProfileController::class, 'teachersList']);

        Route::get('classmates-list', [\App\Http\Controllers\ProfileController::class, 'classmatesList']);

        Route::get('main-page', [\App\Http\Controllers\MainPageController::class, 'mainPage']);

        Route::get('new/{id}', [\App\Http\Controllers\MainPageController::class, 'getNewById']);

        Route::get('video-lessons', [\App\Http\Controllers\LessonsController::class, 'videoLessonsList']);

        Route::get('distance-schedule', [\App\Http\Controllers\DiaryController::class, 'distanceSchedule']);

        Route::get('distance-material/{id}', [\App\Http\Controllers\DiaryController::class, 'distanceMaterialByDiaryId']);

        Route::post('distance-send-answer', [\App\Http\Controllers\DiaryController::class, 'distanceSendAnswer']);

        Route::get('education-recources', [\App\Http\Controllers\LessonsController::class, 'educationRecources']);

        Route::get('school-info', [\App\Http\Controllers\ProfileController::class, 'schoolInfo']);

        Route::post('change-lang', [\App\Http\Controllers\ProfileController::class, 'changeLang']);

        Route::post('change-password', [\App\Http\Controllers\ProfileController::class, 'changePassword']);

        Route::get('predmet-plan/{id}', [\App\Http\Controllers\DiaryController::class, 'predmetPlan']);

        Route::prefix('messenger')->group(function ()
        {
            Route::get('get-chats', [\App\Http\Controllers\MessengerController::class, 'getChats']);

            Route::post('get-chat-history', [\App\Http\Controllers\MessengerController::class, 'getChatHistory']);

            Route::post('send-message', [\App\Http\Controllers\MessengerController::class, 'sendMessage']);

            Route::get('get-users', [\App\Http\Controllers\MessengerController::class, 'getUsers']);
        });
    });
});

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

Route::middleware([App\Http\Middleware\EnsureTokenIsValid::class])->group(function () {
    Route::get('/login', [App\Http\Controllers\AuthController::class, 'login']);

    //Route : paper work - indicator
    Route::get('/indicators/paper-work', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'index'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class]);

    Route::get('/indicators/paper-work/create', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'create'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]);

    Route::post('/indicators/paper-work', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'store'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]);

    Route::get('/indicators/paper-work/{level}/{unit}/{year}/edit', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'edit'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]); //belum

    Route::put('/indicators/paper-work/{level}/{unit}/{year}', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'update'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]); //belum

    Route::delete('/indicators/paper-work/{level}/{unit}/{year}', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'destroy'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]);
    //End Route : paper work - indicator

    //Route : indicator
    Route::get('/indicator', [App\Http\Controllers\IndicatorController::class, 'index'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::post('/indicator', [App\Http\Controllers\IndicatorController::class, 'store'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/indicator/{id}', [App\Http\Controllers\IndicatorController::class, 'show'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::put('/indicator/{id}', [App\Http\Controllers\IndicatorController::class, 'update'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdmin::class]); //belum

    Route::delete('/indicator/{id}', [App\Http\Controllers\IndicatorController::class, 'destroy'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdmin::class]); //belum
    //End Route : indicator

    //Route : reference - indicator
    Route::get('/indicators/reference/create', [App\Http\Controllers\Extends\Indicator\IndicatorReferenceController::class, 'create'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::post('/indicators/reference', [App\Http\Controllers\Extends\Indicator\IndicatorReferenceController::class, 'store'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/indicators/reference/edit', [App\Http\Controllers\Extends\Indicator\IndicatorReferenceController::class, 'edit'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]);

    Route::put('/indicators/reference', [App\Http\Controllers\Extends\Indicator\IndicatorReferenceController::class, 'update'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]);
    //End Route : reference - indicator

    //Route : paper work - target
    Route::get('/targets/paper-work/edit', [App\Http\Controllers\Extends\Target\PaperWorkTargetController::class, 'edit'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class]);

    Route::put('/targets/paper-work', [App\Http\Controllers\Extends\Target\PaperWorkTargetController::class, 'update'])
    ->middleware([App\Http\Middleware\RequestHeaderCheck::class]);
    //End Route : paper work - target
});

Route::get('/level/{slug}/units', [App\Http\Controllers\UnitController::class, 'unitsOfLevel']);
Route::get('/user/{id}/levels', [App\Http\Controllers\LevelController::class, 'levelsOfUser']);

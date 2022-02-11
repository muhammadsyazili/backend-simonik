<?php

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

Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

    //Route : paper work - indicator
    Route::get('/indicators/paper-work', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'index'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class]);

    Route::get('/indicators/paper-work/create', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'create'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]);

    Route::post('/indicators/paper-work', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'store'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class, App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromUrlByLevel::class]); //App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromPost::class

    Route::get('/indicators/paper-work/{level}/{unit}/{year}/edit', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'edit'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class]);

    Route::put('/indicators/paper-work/{level}/{unit}/{year}', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'update'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class, App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromUrlByLevel::class]);

    Route::delete('/indicators/paper-work/{level}/{unit}/{year}', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'destroy'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class, App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromUrlByLevel::class]);

    Route::put('/indicators/paper-work/reorder', [App\Http\Controllers\Extends\Indicator\PaperWorkIndicatorController::class, 'reorder'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class, App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromUrlByLevel::class]); //App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromPost::class
    //End Route : paper work - indicator

    //Route : indicator
    Route::post('/indicator', [App\Http\Controllers\IndicatorController::class, 'store'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/indicator/{id}/edit', [App\Http\Controllers\IndicatorController::class, 'edit'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class, App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromUrlById::class]);

    Route::put('/indicator/{id}', [App\Http\Controllers\IndicatorController::class, 'update'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class, App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromUrlById::class]);

    Route::delete('/indicator/{id}', [App\Http\Controllers\IndicatorController::class, 'destroy'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class, App\Http\Middleware\IsSuperMaster::class]);
    //End Route : indicator

    //Route : reference - indicator
    Route::get('/indicators/reference/create', [App\Http\Controllers\Extends\Indicator\IndicatorReferenceController::class, 'create'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::post('/indicators/reference', [App\Http\Controllers\Extends\Indicator\IndicatorReferenceController::class, 'store'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/indicators/reference/edit', [App\Http\Controllers\Extends\Indicator\IndicatorReferenceController::class, 'edit'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class, App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromUrlByLevel::class]); //App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromQuery::class

    Route::put('/indicators/reference', [App\Http\Controllers\Extends\Indicator\IndicatorReferenceController::class, 'update'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdminOrAdminHaveChild::class, App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromUrlByLevel::class]); //App\Http\Middleware\CurrentLevelNotSameWithUserLevelFromPost::class
    //End Route : reference - indicator

    //Route : paper work - target
    Route::get('/targets/paper-work/edit', [App\Http\Controllers\Extends\Target\PaperWorkTargetController::class, 'edit'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class]);

    Route::put('/targets/paper-work', [App\Http\Controllers\Extends\Target\PaperWorkTargetController::class, 'update'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class]);
    //End Route : paper work - target

    //Route : paper work - realization
    Route::get('/realizations/paper-work/edit', [App\Http\Controllers\Extends\Realization\PaperWorkRealizationController::class, 'edit'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class]);

    Route::put('/realizations/paper-work', [App\Http\Controllers\Extends\Realization\PaperWorkRealizationController::class, 'update'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class]);
    //End Route : paper work - realization

    //Route : user
    Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/user/create', [App\Http\Controllers\UserController::class, 'create'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::post('/user', [App\Http\Controllers\UserController::class, 'store'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/user/{id}/edit', [App\Http\Controllers\UserController::class, 'edit'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::put('/user/{id}', [App\Http\Controllers\UserController::class, 'update'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::delete('/user/{id}', [App\Http\Controllers\UserController::class, 'destroy'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);
    //End Route : user

    //Route : level
    Route::get('/levels', [App\Http\Controllers\LevelController::class, 'index'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/level/create', [App\Http\Controllers\LevelController::class, 'create'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::post('/level', [App\Http\Controllers\LevelController::class, 'store'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/level/{id}/edit', [App\Http\Controllers\LevelController::class, 'edit'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::put('/level/{id}', [App\Http\Controllers\LevelController::class, 'update'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::delete('/level/{id}', [App\Http\Controllers\LevelController::class, 'destroy'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);
    //End Route : level

    //Route : unit
    Route::get('/units', [App\Http\Controllers\UnitController::class, 'index'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/unit/create', [App\Http\Controllers\UnitController::class, 'create'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::post('/unit', [App\Http\Controllers\UnitController::class, 'store'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::get('/unit/{id}/edit', [App\Http\Controllers\UnitController::class, 'edit'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::put('/unit/{id}', [App\Http\Controllers\UnitController::class, 'update'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);

    Route::delete('/unit/{id}', [App\Http\Controllers\UnitController::class, 'destroy'])
        ->middleware([App\Http\Middleware\HasUserIdInHeader::class, App\Http\Middleware\IsSuperAdmin::class]);
    //End Route : unit
});

Route::get('/level/{slug}/units', [App\Http\Controllers\UnitController::class, 'unitsOfLevel']);
Route::get('/user/{id}/levels', [App\Http\Controllers\LevelController::class, 'levelsOfUser']);
Route::get('/realizations/paper-work/{id}/{month}/change-lock', [App\Http\Controllers\Extends\Realization\PaperWorkRealizationController::class, 'changeLock']);

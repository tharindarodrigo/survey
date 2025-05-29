<?php

use Domain\Shared\Controllers\UserController;
use Domain\Shared\Controllers\UserTokenCreateController;
use Domain\Surveys\Controllers\SurveyCreateController;
use Domain\Surveys\Controllers\SurveyDeleteController;
use Domain\Surveys\Controllers\SurveyIndexController;
use Domain\Surveys\Controllers\SurveyResponseCreateController;
use Domain\Surveys\Controllers\SurveyUpdateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/tokens/create', UserTokenCreateController::class)
    ->name('tokens.create');

// Public route (no authentication required)
Route::get('/surveys', SurveyIndexController::class)
    ->name('surveys.index');

Route::post('/surveys/{survey}/responses', SurveyResponseCreateController::class)
    ->middleware(['throttle:10,1']) // 10 requests per minute for rate limiting
    ->name('survey-responses.store');

Route::post('/surveys', SurveyCreateController::class)
    ->middleware(['auth:sanctum'])
    ->name('surveys.store');

Route::put('/surveys/{survey}', SurveyUpdateController::class)
    ->middleware(['auth:sanctum'])
    ->name('surveys.update');

Route::delete('/surveys/{survey}', SurveyDeleteController::class)
    ->middleware(['auth:sanctum'])
    ->name('surveys.destroy');

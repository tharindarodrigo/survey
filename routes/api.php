<?php

use Domain\Surveys\Controllers\SurveyCreateController;
use Domain\Surveys\Controllers\SurveyDeleteController;
use Domain\Surveys\Controllers\SurveyIndexController;
use Domain\Surveys\Controllers\SurveyUpdateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// TODO: refactor this to use a controller
Route::post('/tokens/create', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
        // 'token_name' => 'required|string',
    ]);

    if (! Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
        return response()->json([
            'message' => 'Unauthenticated',
            'errors' => [
                'email' => ['The provided credentials are incorrect.'],
            ],
        ], 401);
    }

    $token = $request->user()->createToken('token');

    return ['token' => $token->plainTextToken];
});

// Public routes (no authentication required)
Route::get('/surveys', SurveyIndexController::class)
    ->name('surveys.index');

Route::post('/surveys', SurveyCreateController::class)
    ->middleware(['auth:sanctum'])
    ->name('surveys.store');

Route::put('/surveys/{survey}', SurveyUpdateController::class)
    ->middleware(['auth:sanctum'])
    ->name('surveys.update');

Route::delete('/surveys/{survey}', SurveyDeleteController::class)
    ->middleware(['auth:sanctum'])
    ->name('surveys.destroy');

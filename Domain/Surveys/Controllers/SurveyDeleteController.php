<?php

namespace Domain\Surveys\Controllers;

use Domain\Surveys\Actions\DeleteSurveyAction;
use Domain\Surveys\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SurveyDeleteController
{
    public function __construct(private DeleteSurveyAction $deleteSurveyAction) {}

    public function __invoke(Survey $survey): JsonResponse
    {
        if (Auth::user()->cannot('delete', $survey)) {
            return response()->json(['message' => 'Unauthorized'], JsonResponse::HTTP_FORBIDDEN);
        }

        $this->deleteSurveyAction->execute($survey);

        return response()->json(['message' => 'Survey deleted successfully'], JsonResponse::HTTP_OK);
    }
}

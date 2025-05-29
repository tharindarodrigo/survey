<?php

namespace Domain\Surveys\Controllers;

use Domain\Surveys\Actions\CreateSurveyAction;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Requests\SurveyCreateRequest;
use Domain\Surveys\Resources\SurveyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SurveyCreateController
{
    public function __construct(private CreateSurveyAction $createSurveyAction) {}

    public function __invoke(SurveyCreateRequest $request): JsonResponse
    {
        if (Auth::user()->cannot('create', Survey::class)) {
            return response()->json(['message' => 'Unauthorized'], JsonResponse::HTTP_FORBIDDEN);
        }

        $survey = $this->createSurveyAction->execute($request->validated());

        return $survey->toResource(SurveyResource::class)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}

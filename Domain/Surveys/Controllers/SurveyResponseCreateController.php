<?php

namespace Domain\Surveys\Controllers;

use Domain\Surveys\Actions\CreateSurveyResponseAction;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Requests\SurveyResponseCreateRequest;
use Domain\Surveys\Resources\SurveyResponseResource;
use Illuminate\Http\JsonResponse;

class SurveyResponseCreateController
{
    public function __construct(private CreateSurveyResponseAction $createSurveyResponseAction) {}

    public function __invoke(SurveyResponseCreateRequest $request, Survey $survey): JsonResponse
    {
        $data = [...$request->validated(), 'survey_id' => $survey->id];

        $surveyResponse = $this->createSurveyResponseAction->execute($data);

        return $surveyResponse->toResource(SurveyResponseResource::class)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}

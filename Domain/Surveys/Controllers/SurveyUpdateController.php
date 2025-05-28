<?php

namespace Domain\Surveys\Controllers;

use Domain\Surveys\Actions\UpdateSurveyAction;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Requests\SurveyUpdateRequest;
use Domain\Surveys\Resources\SurveyResource;
use Illuminate\Http\JsonResponse;

class SurveyUpdateController
{
    public function __construct(private UpdateSurveyAction $updateSurveyAction) {}

    public function __invoke(SurveyUpdateRequest $request, Survey $survey): JsonResponse
    {
        $updatedSurvey = $this->updateSurveyAction->execute($survey, $request->validated());

        return $updatedSurvey->toResource(SurveyResource::class)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_OK);
    }
}

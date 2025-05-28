<?php

namespace Domain\Surveys\Controllers;

use Domain\Surveys\Actions\CreateSurveyAction;
use Domain\Surveys\Dtos\SurveyData;
use Domain\Surveys\Requests\SurveyCreateRequest;
use Domain\Surveys\Resources\SurveyResource;
use Illuminate\Http\JsonResponse;

class SurveyCreateController
{
    public function __construct(private CreateSurveyAction $createSurveyAction) {}

    public function __invoke(SurveyCreateRequest $request): JsonResponse
    {
        $survey = $this->createSurveyAction->execute($request->validated());

        return $survey->toResource(SurveyResource::class)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}

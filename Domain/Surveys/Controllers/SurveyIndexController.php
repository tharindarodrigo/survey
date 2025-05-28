<?php

namespace Domain\Surveys\Controllers;

use Domain\Surveys\Actions\IndexSurveysAction;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Resources\SurveyResource;
use Illuminate\Http\JsonResponse;

class SurveyIndexController
{
    public function __construct(private IndexSurveysAction $indexSurveysAction) {}

    public function __invoke(): JsonResponse
    {
        $surveys = $this->indexSurveysAction->execute();

        return $surveys->toResourceCollection(SurveyResource::class)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_OK);
    }
}

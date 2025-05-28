<?php

namespace Domain\Surveys\Actions;

use Domain\Surveys\Models\SurveyResponse;

class CreateSurveyResponseAction
{
    /**
     * Execute the action to create a new survey response.
     *
     * @param array $data
     * @return SurveyResponse
     */
    public function execute(array $data): SurveyResponse
    {
        return SurveyResponse::query()->create($data);
    }
}

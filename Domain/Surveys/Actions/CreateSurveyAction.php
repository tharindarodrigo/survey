<?php

namespace Domain\Surveys\Actions;

use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;

class CreateSurveyAction
{
    /**
     * Execute the action to create a new survey.
     */
    public function execute(array $data): Survey
    {
        $data = [
            ...$data,
            'status' => SurveyStatus::ACTIVE,
        ];

        return Survey::query()->create($data);
    }
}

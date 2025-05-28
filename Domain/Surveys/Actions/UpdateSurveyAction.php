<?php

namespace Domain\Surveys\Actions;

use Domain\Surveys\Models\Survey;

class UpdateSurveyAction
{
    /**
     * Execute the action to update an existing survey.
     */
    public function execute(Survey $survey, array $data): Survey
    {
        $survey->update($data);

        return $survey->fresh();
    }
}

<?php

namespace Domain\Surveys\Actions;

use Domain\Surveys\Models\Survey;

class UpdateSurveyAction
{
    /**
     * Execute the action to update an existing survey.
     *
     * @param Survey $survey
     * @param array $data
     * @return Survey
     */
    public function execute(Survey $survey, array $data): Survey
    {
        $survey->update($data);

        return $survey->fresh();
    }
}

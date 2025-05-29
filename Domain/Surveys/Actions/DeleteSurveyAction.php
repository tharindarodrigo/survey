<?php

namespace Domain\Surveys\Actions;

use Domain\Surveys\Models\Survey;

class DeleteSurveyAction
{
    /**
     * Execute the action to soft delete a survey.
     */
    public function execute(Survey $survey): bool
    {
        return $survey->delete();
    }
}

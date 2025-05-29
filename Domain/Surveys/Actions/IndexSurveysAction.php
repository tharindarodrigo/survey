<?php

namespace Domain\Surveys\Actions;

use Domain\Surveys\Models\Survey;
use Illuminate\Database\Eloquent\Collection;

class IndexSurveysAction
{
    /**
     * Execute the action to retrieve all non-deleted surveys.
     */
    public function execute(): Collection
    {
        return Survey::query()->get();
    }
}

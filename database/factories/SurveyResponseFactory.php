<?php

namespace Database\Factories;

use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyResponseFactory extends Factory
{
    protected $model = SurveyResponse::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'participant_email' => $this->faker->safeEmail,
            'response_text' => $this->faker->text(500),
        ];
    }
}

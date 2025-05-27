<?php

namespace Database\Factories;

use Domain\Surveys\Enums\Sentiment;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveySummary;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveySummaryFactory extends Factory
{
    protected $model = SurveySummary::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'summary_text' => $this->faker->text(300),
            'sentiment' => $this->faker->randomElement(Sentiment::cases()),
            'topics_json' => $this->faker->words(5),
        ];
    }
}

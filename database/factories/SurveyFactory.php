<?php

namespace Database\Factories;

use Domain\Companies\Models\Company;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->text(200),
            'status' => $this->faker->randomElement(SurveyStatus::cases()),
        ];
    }
}

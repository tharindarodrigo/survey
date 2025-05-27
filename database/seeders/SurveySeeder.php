<?php

namespace Database\Seeders;

use Domain\Companies\Models\Company;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveyResponse;
use Domain\Surveys\Models\SurveySummary;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        // Create a simple company and survey for testing
        $company = Company::factory()->create([
            'name' => 'Test Company',
        ]);

        $survey = Survey::factory()->create([
            'company_id' => $company->id,
            'title' => 'Customer Satisfaction Survey',
            'description' => 'Please share your feedback about our services.',
            'status' => 'active',
        ]);

        // Create a few sample responses
        SurveyResponse::factory(3)->create([
            'survey_id' => $survey->id,
        ]);

        // Create a summary
        SurveySummary::factory()->create([
            'survey_id' => $survey->id,
        ]);
    }
}

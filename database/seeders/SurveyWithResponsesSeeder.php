<?php

namespace Database\Seeders;

use Domain\Companies\Models\Company;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveyResponse;
use Illuminate\Database\Seeder;

class SurveyWithResponsesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a company if none exists
        $company = Company::firstOrCreate([
            'name' => 'Test Company',
        ]);

        // Create a completed survey with responses
        $survey = Survey::create([
            'company_id' => $company->id,
            'title' => 'Customer Satisfaction Survey',
            'description' => 'A survey to measure customer satisfaction with our services.',
            'status' => SurveyStatus::COMPLETED,
        ]);

        // Create sample responses
        $responses = [
            [
                'participant_email' => 'customer1@example.com',
                'response_text' => 'I am very satisfied with the service quality. The team was professional and responsive to my needs. The delivery was on time and the product quality exceeded my expectations.',
            ],
            [
                'participant_email' => 'customer2@example.com',
                'response_text' => 'The service was okay but there were some delays in communication. The final result was good but the process could be improved. I would recommend better project management.',
            ],
            [
                'participant_email' => 'customer3@example.com',
                'response_text' => 'Excellent experience! The team went above and beyond to ensure my requirements were met. Very impressed with the attention to detail and customer service.',
            ],
            [
                'participant_email' => 'customer4@example.com',
                'response_text' => 'I had some issues with the billing process and the customer support was slow to respond. However, once the issue was resolved, everything worked well.',
            ],
            [
                'participant_email' => 'customer5@example.com',
                'response_text' => 'Great service overall. The platform is user-friendly and the features meet most of my business needs. Price is reasonable for the value provided.',
            ],
        ];

        foreach ($responses as $responseData) {
            SurveyResponse::create([
                'survey_id' => $survey->id,
                'participant_email' => $responseData['participant_email'],
                'response_text' => $responseData['response_text'],
            ]);
        }

        $this->command->info("Created survey '{$survey->title}' with ".count($responses).' responses.');
    }
}

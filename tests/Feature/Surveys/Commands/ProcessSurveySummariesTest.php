<?php

namespace Tests\Feature\Surveys\Commands;

use Domain\Companies\Models\Company;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Jobs\ProcessSurveySummary;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('Process Survey Summaries Command', function () {

    it('finds completed surveys with responses and dispatches jobs', function () {
        Queue::fake();

        // Create test data
        $company = Company::factory()->create();

        // Create a completed survey with responses
        $completedSurvey = Survey::factory()->create([
            'company_id' => $company->id,
            'status' => SurveyStatus::COMPLETED,
        ]);

        SurveyResponse::factory()->count(3)->create([
            'survey_id' => $completedSurvey->id,
        ]);

        // Create an active survey (should be ignored)
        $activeSurvey = Survey::factory()->create([
            'company_id' => $company->id,
            'status' => SurveyStatus::ACTIVE,
        ]);

        SurveyResponse::factory()->count(2)->create([
            'survey_id' => $activeSurvey->id,
        ]);

        // Create a completed survey without responses (should be ignored)
        $emptySurvey = Survey::factory()->create([
            'company_id' => $company->id,
            'status' => SurveyStatus::COMPLETED,
        ]);

        // Run the command
        $this->artisan('surveys:process-summaries')
            ->expectsOutput('Starting survey summary processing...')
            ->expectsOutput('Found 1 survey(s) to process.')
            ->expectsOutput('All survey summary jobs have been dispatched to the queue.')
            ->assertExitCode(0);

        // Assert that job was dispatched for the correct survey
        Queue::assertPushed(ProcessSurveySummary::class, function ($job) use ($completedSurvey) {
            return $job->survey->id === $completedSurvey->id;
        });

        // Assert only one job was dispatched
        Queue::assertPushed(ProcessSurveySummary::class, 1);
    });

    it('processes specific survey when survey-id option is provided', function () {
        Queue::fake();

        $company = Company::factory()->create();

        $survey1 = Survey::factory()->create([
            'company_id' => $company->id,
            'status' => SurveyStatus::COMPLETED,
        ]);
        SurveyResponse::factory()->create(['survey_id' => $survey1->id]);

        $survey2 = Survey::factory()->create([
            'company_id' => $company->id,
            'status' => SurveyStatus::COMPLETED,
        ]);
        SurveyResponse::factory()->create(['survey_id' => $survey2->id]);

        // Run command for specific survey
        $this->artisan('surveys:process-summaries', ['--survey-id' => $survey1->id])
            ->expectsOutput('Found 1 survey(s) to process.')
            ->assertExitCode(0);

        // Assert job was dispatched only for the specified survey
        Queue::assertPushed(ProcessSurveySummary::class, function ($job) use ($survey1) {
            return $job->survey->id === $survey1->id;
        });

        Queue::assertPushed(ProcessSurveySummary::class, 1);
    });

    it('shows message when no surveys need processing', function () {
        // Create only active surveys or surveys without responses
        $company = Company::factory()->create();

        Survey::factory()->create([
            'company_id' => $company->id,
            'status' => SurveyStatus::ACTIVE,
        ]);

        $this->artisan('surveys:process-summaries')
            ->expectsOutput('No surveys found that need summary processing.')
            ->assertExitCode(0);
    });

    it('uses batch processing with custom batch size', function () {
        Bus::fake();

        $company = Company::factory()->create();

        // Create multiple completed surveys with responses
        for ($i = 0; $i < 5; $i++) {
            $survey = Survey::factory()->create([
                'company_id' => $company->id,
                'status' => SurveyStatus::COMPLETED,
            ]);
            SurveyResponse::factory()->create(['survey_id' => $survey->id]);
        }

        $this->artisan('surveys:process-summaries', ['--batch-size' => 2])
            ->expectsOutput('Found 5 survey(s) to process.')
            ->expectsOutput('Processing surveys in 3 batch(es) of 2 surveys each.')
            ->assertExitCode(0);

        // Assert that 3 batches were dispatched
        Bus::assertBatchCount(3);
    });

    it('skips surveys that already have summaries unless force flag is used', function () {
        Queue::fake();

        $company = Company::factory()->create();

        $survey = Survey::factory()->create([
            'company_id' => $company->id,
            'status' => SurveyStatus::COMPLETED,
        ]);

        SurveyResponse::factory()->create(['survey_id' => $survey->id]);

        // Create a summary for this survey
        $survey->summary()->create([
            'summary_text' => 'Existing summary',
            'sentiment' => 'positive',
            'topics_json' => ['topic1', 'topic2'],
        ]);

        // Run without force flag - should find no surveys
        $this->artisan('surveys:process-summaries')
            ->expectsOutput('No surveys found that need summary processing.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();

        // Run with force flag - should process the survey
        $this->artisan('surveys:process-summaries', ['--force' => true])
            ->expectsOutput('Found 1 survey(s) to process.')
            ->assertExitCode(0);

        Queue::assertPushed(ProcessSurveySummary::class, 1);
    });
});

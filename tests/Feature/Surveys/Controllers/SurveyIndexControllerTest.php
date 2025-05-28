<?php

use Domain\Companies\Models\Company;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
});

describe('Public Survey Index Access', function () {

    it('allows public access without authentication', function () {
        $survey = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Public Survey',
            'description' => 'A publicly accessible survey',
            'status' => SurveyStatus::ACTIVE,
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'title',
                        'description',
                        'status',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])
            ->assertJson([
                'data' => [
                    [
                        'id' => $survey->id,
                        'company_id' => $this->company->id,
                        'title' => 'Public Survey',
                        'description' => 'A publicly accessible survey',
                        'status' => SurveyStatus::ACTIVE->value,
                    ]
                ]
            ]);
    });

    it('returns multiple surveys', function () {
        $survey1 = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'First Survey',
            'status' => SurveyStatus::ACTIVE,
        ]);

        $survey2 = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Second Survey',
            'status' => SurveyStatus::COMPLETED,
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $survey1->id,
                        'title' => 'First Survey',
                        'status' => SurveyStatus::ACTIVE->value,
                    ],
                    [
                        'id' => $survey2->id,
                        'title' => 'Second Survey',
                        'status' => SurveyStatus::COMPLETED->value,
                    ]
                ]
            ]);
    });

    it('returns empty array when no surveys exist', function () {
        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJson([
                'data' => []
            ]);
    });
});

describe('Survey Data Filtering', function () {

    it('excludes soft deleted surveys', function () {
        $activeSurvey = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Active Survey',
            'status' => SurveyStatus::ACTIVE,
        ]);

        $deletedSurvey = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Deleted Survey',
            'status' => SurveyStatus::ACTIVE,
        ]);

        // Soft delete one survey
        $deletedSurvey->delete();

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $activeSurvey->id,
                        'title' => 'Active Survey',
                    ]
                ]
            ]);

        // Verify deleted survey is not in response
        $responseData = $response->json('data');
        $surveyIds = collect($responseData)->pluck('id')->toArray();
        expect($surveyIds)->not->toContain($deletedSurvey->id);
    });

    it('includes surveys from different companies', function () {
        $company2 = Company::factory()->create();

        $survey1 = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Company 1 Survey',
        ]);

        $survey2 = Survey::factory()->create([
            'company_id' => $company2->id,
            'title' => 'Company 2 Survey',
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $responseData = $response->json('data');
        $companyIds = collect($responseData)->pluck('company_id')->toArray();
        expect($companyIds)->toContain($this->company->id, $company2->id);
    });

    it('includes surveys with different statuses', function () {
        Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Active Survey',
            'status' => SurveyStatus::ACTIVE,
        ]);

        Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Completed Survey',
            'status' => SurveyStatus::COMPLETED,
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $responseData = $response->json('data');
        $statuses = collect($responseData)->pluck('status')->toArray();
        expect($statuses)->toContain(SurveyStatus::ACTIVE->value, SurveyStatus::COMPLETED->value);
    });
});

describe('Survey Response Format', function () {

    it('returns correct JSON structure', function () {
        $survey = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Test Survey',
            'description' => 'Test Description',
            'status' => SurveyStatus::ACTIVE,
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'title',
                        'description',
                        'status',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);

        $surveyData = $response->json('data.0');
        expect($surveyData['id'])->toBe($survey->id)
            ->and($surveyData['company_id'])->toBe($this->company->id)
            ->and($surveyData['title'])->toBe('Test Survey')
            ->and($surveyData['description'])->toBe('Test Description')
            ->and($surveyData['status'])->toBe(SurveyStatus::ACTIVE->value)
            ->and($surveyData['created_at'])->not->toBeNull()
            ->and($surveyData['updated_at'])->not->toBeNull();
    });

    it('handles null description correctly', function () {
        $survey = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Survey without description',
            'description' => null,
            'status' => SurveyStatus::ACTIVE,
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'id' => $survey->id,
                        'title' => 'Survey without description',
                        'description' => null,
                    ]
                ]
            ]);
    });

    it('returns correct content type', function () {
        Survey::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    });
});

describe('Survey Index Edge Cases', function () {

    it('handles large number of surveys', function () {
        Survey::factory(50)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJsonCount(50, 'data');
    });

    it('handles surveys with special characters', function () {
        $survey = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Survey with émojis 🎯 and special chars: áéíóú',
            'description' => 'Description with special characters: <>&"\'',
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'id' => $survey->id,
                        'title' => 'Survey with émojis 🎯 and special chars: áéíóú',
                        'description' => 'Description with special characters: <>&"\'',
                    ]
                ]
            ]);
    });

    it('maintains consistent ordering', function () {
        $surveys = Survey::factory(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response1 = $this->getJson('/api/surveys');
        $response2 = $this->getJson('/api/surveys');

        $ids1 = collect($response1->json('data'))->pluck('id')->toArray();
        $ids2 = collect($response2->json('data'))->pluck('id')->toArray();

        expect($ids1)->toEqual($ids2);
    });
});

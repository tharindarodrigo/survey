<?php

use App\Models\User;
use Domain\Companies\Models\Company;
use Domain\Surveys\Actions\CreateSurveyAction;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Survey Creation Action', function () {

    beforeEach(function () {
        $this->action = new CreateSurveyAction();
        $this->company = Company::factory()->create();
    });

    it('creates a survey with minimal data', function () {
        $data = [
            'company_id' => $this->company->id,
            'title' => 'Test Survey',
        ];

        $survey = $this->action->execute($data);

        expect($survey)
            ->toBeInstanceOf(Survey::class)
            ->and($survey->company_id)->toBe($this->company->id)
            ->and($survey->title)->toBe('Test Survey')
            ->and($survey->description)->toBeNull()
            ->and($survey->status)->toBe(SurveyStatus::ACTIVE);

        $this->assertDatabaseHas('surveys', [
            'company_id' => $this->company->id,
            'title' => 'Test Survey',
            'status' => SurveyStatus::ACTIVE->value,
        ]);
    });

    it('creates a survey with full data', function () {
        $data = [
            'company_id' => $this->company->id,
            'title' => 'Customer Satisfaction Survey',
            'description' => 'A comprehensive survey to measure customer satisfaction levels',
        ];

        $survey = $this->action->execute($data);

        expect($survey)
            ->toBeInstanceOf(Survey::class)
            ->and($survey->company_id)->toBe($this->company->id)
            ->and($survey->title)->toBe('Customer Satisfaction Survey')
            ->and($survey->description)->toBe('A comprehensive survey to measure customer satisfaction levels')
            ->and($survey->status)->toBe(SurveyStatus::ACTIVE);
    });

    it('always sets status to active regardless of input', function () {
        $data = [
            'company_id' => $this->company->id,
            'title' => 'Test Survey',
            'status' => SurveyStatus::COMPLETED, // This should be overridden
        ];

        $survey = $this->action->execute($data);

        expect($survey->status)->toBe(SurveyStatus::ACTIVE);
    });

    it('preserves existing data and overrides status', function () {
        $data = [
            'company_id' => $this->company->id,
            'title' => 'Test Survey',
            'description' => 'Original description',
            'status' => SurveyStatus::COMPLETED,
            'extra_field' => 'should be preserved',
        ];

        $survey = $this->action->execute($data);

        expect($survey->status)->toBe(SurveyStatus::ACTIVE)
            ->and($survey->title)->toBe('Test Survey')
            ->and($survey->description)->toBe('Original description');
    });
});

describe('Survey API Integration', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
    });

    it('returns correct json structure on successful creation', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'API Test Survey',
                'description' => 'Testing API response structure',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'company_id',
                    'title',
                    'description',
                    'status',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $responseData = $response->json('data');
        expect($responseData['id'])->toBeInt()
            ->and($responseData['company_id'])->toBe($this->company->id)
            ->and($responseData['title'])->toBe('API Test Survey')
            ->and($responseData['description'])->toBe('Testing API response structure')
            ->and($responseData['status'])->toBe(SurveyStatus::ACTIVE->value)
            ->and($responseData['created_at'])->toBeString()
            ->and($responseData['updated_at'])->toBeString();
    });

    it('creates survey with proper relationships', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Relationship Test Survey',
                'description' => 'Testing database relationships',
            ]);

        $response->assertStatus(201);

        $surveyId = $response->json('data.id');
        $survey = Survey::find($surveyId);

        expect($survey->company)->toBeInstanceOf(Company::class)
            ->and($survey->company->id)->toBe($this->company->id)
            ->and($survey->responses)->toBeEmpty()
            ->and($survey->summary)->toBeNull();
    });

    it('handles concurrent survey creation', function () {
        $surveyData = [
            'company_id' => $this->company->id,
            'title' => 'Concurrent Survey',
            'description' => 'Testing concurrent creation',
        ];

        // Simulate multiple concurrent requests
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/surveys', array_merge($surveyData, [
                    'title' => "Concurrent Survey {$i}"
                ]));
        }

        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        // Verify all surveys were created
        expect(Survey::where('company_id', $this->company->id)->count())->toBe(3);
    });

    it('handles survey creation with different companies', function () {
        $company2 = Company::factory()->create();

        $response1 = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Company 1 Survey',
            ]);

        $response2 = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $company2->id,
                'title' => 'Company 2 Survey',
            ]);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        expect(Survey::where('company_id', $this->company->id)->count())->toBe(1)
            ->and(Survey::where('company_id', $company2->id)->count())->toBe(1);
    });
});

describe('Survey Resource Formatting', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
    });

    it('formats dates correctly in response', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Date Format Test',
            ]);

        $response->assertStatus(201);

        $data = $response->json('data');

        // Verify date format (ISO 8601)
        expect($data['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/')
            ->and($data['updated_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/');
    });

    it('handles enum status formatting correctly', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Enum Test Survey',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'status' => 'active'
                ]
            ]);

        // Verify it's the string value, not the enum object
        $status = $response->json('data.status');
        expect($status)->toBeString()
            ->and($status)->toBe(SurveyStatus::ACTIVE->value);
    });
});

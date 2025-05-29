<?php

use Domain\Companies\Models\Company;
use Domain\Shared\Models\User;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Permissions\SurveyPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();

    // Create a role and assign Permissions within the SurveyPermission
    $this->role = Role::create(['name' => 'survey_creator', 'guard_name' => 'api']);
    $this->role->syncPermissions([
        SurveyPermission::CREATE->value,
    ]);
    $this->user->assignRole($this->role);
});

describe('Permission Check for Survey Creation', function () {

    it('allows user with permission to create survey', function () {

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
                'description' => 'This is a test survey',
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
                ],
            ]);
    });

    it('denies user without permission to create survey', function () {
        // Remove the permission from the role
        $this->role->revokePermissionTo(SurveyPermission::CREATE->value);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Unauthorized Survey',
                'description' => 'This should not be allowed',
            ]);

        $response->assertStatus(403);
    });
});

describe('Survey API Integration', function () {

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
                ],
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
                    'title' => "Concurrent Survey {$i}",
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

    it('handles enum status formatting correctly', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Enum Test Survey',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'status' => 'active',
                ],
            ]);

        // Verify it's the string value, not the enum object
        $status = $response->json('data.status');
        expect($status)->toBeString()
            ->and($status)->toBe(SurveyStatus::ACTIVE->value);
    });
});

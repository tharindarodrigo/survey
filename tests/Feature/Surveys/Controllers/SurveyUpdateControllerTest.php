<?php

use App\Models\User;
use Domain\Companies\Models\Company;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Permissions\SurveyPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->survey = Survey::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Original Survey Title',
        'description' => 'Original survey description',
        'status' => SurveyStatus::ACTIVE,
    ]);

    // Create role with survey update permission
    $this->role = Role::create(['name' => 'survey_editor', 'guard_name' => 'api']);
    $this->role->syncPermissions([
        SurveyPermission::UPDATE->value,
        SurveyPermission::VIEW->value,
    ]);
    $this->user->assignRole($this->role);
});

describe('Survey Update Validation', function () {

    it('requires authentication', function () {
        $response = $this->putJson("/api/surveys/{$this->survey->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(401);
    });

    it('updates a survey with valid data', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'title' => 'Updated Customer Survey',
                'description' => 'Updated survey description',
                'status' => SurveyStatus::COMPLETED->value,
            ]);

        $response->assertStatus(200)
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
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->survey->id,
                    'company_id' => $this->company->id,
                    'title' => 'Updated Customer Survey',
                    'description' => 'Updated survey description',
                    'status' => SurveyStatus::COMPLETED->value,
                ]
            ]);

        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'title' => 'Updated Customer Survey',
            'description' => 'Updated survey description',
            'status' => SurveyStatus::COMPLETED->value,
        ]);
    });

    it('updates a survey with partial data', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'title' => 'Only Title Updated',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->survey->id,
                    'title' => 'Only Title Updated',
                    'description' => 'Original survey description', // Should remain unchanged
                    'status' => SurveyStatus::ACTIVE->value, // Should remain unchanged
                ]
            ]);
    });

    it('returns 404 for non-existent survey', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/surveys/99999', [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(404);
    });
});

describe('Survey Update Validation Rules', function () {

    it('validates company_id exists when provided', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'company_id' => 99999, // Non-existent company
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id'])
            ->assertJsonFragment([
                'company_id' => ['The selected company id is invalid.']
            ]);
    });

    it('validates title is string when provided', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'title' => 123,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title'])
            ->assertJsonFragment([
                'title' => ['The title field must be a string.']
            ]);
    });

    it('validates title maximum length', function () {
        $longTitle = str_repeat('a', 256);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'title' => $longTitle,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title'])
            ->assertJsonFragment([
                'title' => ['The title field must not be greater than 255 characters.']
            ]);
    });

    it('allows title to be exactly 255 characters', function () {
        $exactTitle = str_repeat('a', 255);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'title' => $exactTitle,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => $exactTitle,
                ]
            ]);
    });

    it('validates description is string when provided', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'description' => 123,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description'])
            ->assertJsonFragment([
                'description' => ['The description field must be a string.']
            ]);
    });

    it('validates description maximum length', function () {
        $longDescription = str_repeat('a', 1001);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'description' => $longDescription,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description'])
            ->assertJsonFragment([
                'description' => ['The description field must not be greater than 1000 characters.']
            ]);
    });

    it('allows description to be exactly 1000 characters', function () {
        $exactDescription = str_repeat('a', 1000);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'description' => $exactDescription,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'description' => $exactDescription,
                ]
            ]);
    });

    it('allows description to be null', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'description' => null,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'description' => null,
                ]
            ]);
    });

    it('validates status is valid enum value when provided', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    it('allows valid status values', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'status' => SurveyStatus::COMPLETED->value,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => SurveyStatus::COMPLETED->value,
                ]
            ]);
    });

    it('accepts empty request body', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", []);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->survey->id,
                    'title' => 'Original Survey Title',
                    'description' => 'Original survey description',
                    'status' => SurveyStatus::ACTIVE->value,
                ]
            ]);
    });

    it('ignores extra fields not in validation rules', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'title' => 'Updated Title',
                'extra_field' => 'Should be ignored',
                'another_field' => 'Also ignored',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated Title',
                ]
            ]);

        // Verify extra fields are not in database
        $this->assertDatabaseMissing('surveys', [
            'extra_field' => 'Should be ignored',
        ]);
    });
});

describe('Survey Update Edge Cases', function () {

    it('handles empty string values correctly', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'title' => '', // Empty string shouldn't be allowed
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'title' => ['The title field is required.'],
            ]);
    });

    it('handles status transitions', function () {
        // Test ACTIVE -> COMPLETED
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'status' => SurveyStatus::COMPLETED->value,
            ]);

        $response1->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => SurveyStatus::COMPLETED->value,
                ]
            ]);

        // Test COMPLETED -> ACTIVE
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", [
                'status' => SurveyStatus::ACTIVE->value,
            ]);

        $response2->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => SurveyStatus::ACTIVE->value,
                ]
            ]);
    });

    it('handles malformed json request', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->call('PUT', "/api/surveys/{$this->survey->id}", [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], 'invalid json');

        // Laravel treats malformed JSON as empty request, which is valid for updates
        $response->assertStatus(200);
    });

    it('preserves original data when no updates provided', function () {
        $originalData = [
            'title' => $this->survey->title,
            'description' => $this->survey->description,
            'status' => $this->survey->status->value,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/surveys/{$this->survey->id}", []);

        $response->assertStatus(200)
            ->assertJson([
                'data' => $originalData
            ]);
    });
});

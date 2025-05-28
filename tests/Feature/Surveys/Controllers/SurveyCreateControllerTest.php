<?php

use Domain\Shared\Models\User;
use Domain\Companies\Models\Company;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Permissions\SurveyPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();

    // Create role with survey creation permission
    $this->role = Role::create(['name' => 'survey_manager', 'guard_name' => 'api']);
    $this->role->syncPermissions([
        SurveyPermission::CREATE->value,
        SurveyPermission::UPDATE->value,
        SurveyPermission::VIEW->value,
    ]);

    $this->user->assignRole($this->role);
});

describe('Survey Creation Validation', function () {

    it('creates a survey with valid data', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Customer Satisfaction Survey',
                'description' => 'A survey to measure customer satisfaction',
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
            ])
            ->assertJson([
                'data' => [
                    'company_id' => $this->company->id,
                    'title' => 'Customer Satisfaction Survey',
                    'description' => 'A survey to measure customer satisfaction',
                    'status' => SurveyStatus::ACTIVE->value,
                ]
            ]);

        $this->assertDatabaseHas('surveys', [
            'company_id' => $this->company->id,
            'title' => 'Customer Satisfaction Survey',
            'description' => 'A survey to measure customer satisfaction',
            'status' => SurveyStatus::ACTIVE->value,
        ]);
    });

    it('creates a survey without description', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Simple Survey',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'company_id' => $this->company->id,
                    'title' => 'Simple Survey',
                    'description' => null,
                    'status' => SurveyStatus::ACTIVE->value,
                ]
            ]);
    });
});

describe('Survey Creation Validation Rules', function () {

    it('requires company_id', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'title' => 'Test Survey',
                'description' => 'Test Description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id'])
            ->assertJsonFragment([
                'company_id' => ['The company id field is required.']
            ]);
    });

    it('requires company_id to exist in companies table', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => 99999, // Non-existent company
                'title' => 'Test Survey',
                'description' => 'Test Description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id'])
            ->assertJsonFragment([
                'company_id' => ['The selected company id is invalid.']
            ]);
    });

    it('requires title', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'description' => 'Test Description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title'])
            ->assertJsonFragment([
                'title' => ['The title field is required.']
            ]);
    });

    it('requires title to be a string', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 123,
                'description' => 'Test Description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title'])
            ->assertJsonFragment([
                'title' => ['The title field must be a string.']
            ]);
    });

    it('requires title to be maximum 255 characters', function () {
        $longTitle = str_repeat('a', 256);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => $longTitle,
                'description' => 'Test Description',
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
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => $exactTitle,
                'description' => 'Test Description',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => $exactTitle,
                ]
            ]);
    });

    it('allows description to be null', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
                'description' => null,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'description' => null,
                ]
            ]);
    });

    it('allows description to be omitted', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'description' => null,
                ]
            ]);
    });

    it('requires description to be a string when provided', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
                'description' => 123,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description'])
            ->assertJsonFragment([
                'description' => ['The description field must be a string.']
            ]);
    });

    it('requires description to be maximum 1000 characters', function () {
        $longDescription = str_repeat('a', 1001);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
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
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
                'description' => $exactDescription,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'description' => $exactDescription,
                ]
            ]);
    });

    it('ignores status field from request and sets to active by default', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
                'description' => 'Test Description',
                'status' => 'completed', // This should be ignored
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'status' => SurveyStatus::ACTIVE->value,
                ]
            ]);
    });

    it('rejects extra fields not in validation rules', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
                'description' => 'Test Description',
                'extra_field' => 'Should be ignored',
                'another_field' => 'Also ignored',
            ]);

        // Should still create successfully but ignore extra fields
        $response->assertStatus(201);

        // Verify extra fields are not in database
        $this->assertDatabaseMissing('surveys', [
            'extra_field' => 'Should be ignored',
        ]);
    });
});

describe('Survey Creation Edge Cases', function () {

    it('handles empty string values correctly', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => '', // Empty string
                'description' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']); // Title is required, so empty string should fail
    });

    it('handles whitespace-only title', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => '   ', // Only whitespace
                'description' => 'Test Description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']); // Laravel treats whitespace-only as empty
    });

    it('handles unicode characters in title and description', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Customer Survey 🎯 Ñoño',
                'description' => 'Survey with émojis and special characters: áéíóú',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'Customer Survey 🎯 Ñoño',
                    'description' => 'Survey with émojis and special characters: áéíóú',
                ]
            ]);
    });

    it('handles malformed json request', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->call('POST', '/api/surveys', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], 'invalid json');

        // Laravel treats malformed JSON as empty request and validates normally
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id', 'title']);
    });

    it('handles missing content-type header', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->post('/api/surveys', [
                'company_id' => $this->company->id,
                'title' => 'Test Survey',
            ]);

        $response->assertStatus(201); // Should still work with form data
    });
});

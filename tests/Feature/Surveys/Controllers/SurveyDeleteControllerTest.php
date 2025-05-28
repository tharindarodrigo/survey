<?php

use Domain\Shared\Models\User;
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
        'title' => 'Survey to Delete',
        'description' => 'This survey will be deleted',
        'status' => SurveyStatus::ACTIVE,
    ]);

    // Create role with survey delete permission
    $this->role = Role::create(['name' => 'survey_deleter', 'guard_name' => 'api']);
    $this->role->syncPermissions([
        SurveyPermission::DELETE->value,
    ]);

    $this->user->assignRole($this->role);
});

describe('Survey Delete Authorization', function () {

    it('requires authentication', function () {
        $response = $this->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(401);
    });

    it('deletes survey with valid permissions', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(204);

        // Verify survey is soft deleted
        expect(Survey::find($this->survey->id))->toBeNull()
            ->and(Survey::withTrashed()->find($this->survey->id))->not->toBeNull();
    });

    it('denies user without delete permission', function () {
        // Remove delete permission
        $this->role->revokePermissionTo(SurveyPermission::DELETE->value);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized'
            ]);

        // Verify survey still exists
        expect(Survey::find($this->survey->id))->not->toBeNull();
    });

    it('returns 404 for non-existent survey', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/surveys/99999');

        $response->assertStatus(404);
    });
});

describe('Survey Delete Functionality', function () {

    it('soft deletes active survey', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(204);

        // Check database state
        $this->assertSoftDeleted('surveys', [
            'id' => $this->survey->id,
        ]);

        // Verify survey can still be found with trashed
        expect(Survey::withTrashed()->find($this->survey->id))->not->toBeNull()
            ->and(Survey::withTrashed()->find($this->survey->id)->deleted_at)->not->toBeNull();
    });

    it('soft deletes completed survey', function () {
        $this->survey->update(['status' => SurveyStatus::COMPLETED]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('surveys', [
            'id' => $this->survey->id,
        ]);
    });

    it('preserves survey data after soft delete', function () {
        $originalTitle = $this->survey->title;
        $originalDescription = $this->survey->description;

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(204);

        $deletedSurvey = Survey::withTrashed()->find($this->survey->id);
        expect($deletedSurvey->title)->toBe($originalTitle)
            ->and($deletedSurvey->description)->toBe($originalDescription);
    });

    it('does not affect other surveys', function () {
        $otherSurvey = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Other Survey',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(204);

        // Other survey should still exist
        expect(Survey::find($otherSurvey->id))->not->toBeNull()
            ->and(Survey::count())->toBe(1);
    });

    it('excludes soft deleted survey from normal queries', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(204);

        expect(Survey::count())->toBe(0)
            ->and(Survey::withTrashed()->count())->toBe(1);
    });

    it('allows creating new survey with same title and company after soft delete', function () {
        $originalTitle = $this->survey->title;
        $originalCompanyId = $this->survey->company_id;

        // Soft delete the original survey
        $deleteResponse = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $deleteResponse->assertStatus(204);

        // Create a new survey with the same title and company
        $this->role->givePermissionTo(SurveyPermission::CREATE->value);

        $createResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/surveys', [
                'company_id' => $originalCompanyId,
                'title' => $originalTitle,
                'description' => 'New survey with same title',
            ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => $originalTitle,
                    'company_id' => $originalCompanyId,
                    'description' => 'New survey with same title',
                ]
            ]);

        // Verify we now have one active survey and one soft-deleted survey
        expect(Survey::count())->toBe(1)
            ->and(Survey::withTrashed()->count())->toBe(2)
            ->and(Survey::onlyTrashed()->count())->toBe(1);

        // Verify the new survey is different from the soft-deleted one
        $newSurvey = Survey::first();
        $oldSurvey = Survey::onlyTrashed()->first();

        expect($newSurvey->id)->not->toBe($oldSurvey->id)
            ->and($newSurvey->title)->toBe($oldSurvey->title)
            ->and($newSurvey->company_id)->toBe($oldSurvey->company_id)
            ->and($newSurvey->description)->toBe('New survey with same title')
            ->and($oldSurvey->description)->toBe('This survey will be deleted');
    });
});

describe('Survey Delete Edge Cases', function () {

    it('handles survey with related data', function () {
        // Create related survey responses and summary
        $this->survey->responses()->create([
            'participant_email' => 'test@example.com',
            'response_text' => 'Great survey!',
        ]);

        $this->survey->summary()->create([
            'summary_text' => 'Overall positive feedback',
            'sentiment' => 'positive',
            'topics_json' => ['quality', 'service'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(204);

        // Survey should be soft deleted
        $this->assertSoftDeleted('surveys', [
            'id' => $this->survey->id,
        ]);

        // Related data should still exist (due to cascade behavior)
        expect($this->survey->responses()->count())->toBe(1)
            ->and($this->survey->summary)->not->toBeNull();
    });

    it('allows deletion by user with multiple roles', function () {
        $additionalRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $additionalRole->syncPermissions([SurveyPermission::DELETE->value]);
        $this->user->assignRole($additionalRole);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response->assertStatus(204);
    });

    it('handles concurrent deletion attempts gracefully', function () {
        // First deletion
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response1->assertStatus(204);

        // Second deletion attempt should return 404 (survey no longer found in normal queries)
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/surveys/{$this->survey->id}");

        $response2->assertStatus(404);
    });
});

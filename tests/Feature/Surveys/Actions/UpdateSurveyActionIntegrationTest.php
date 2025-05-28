<?php

use Domain\Companies\Models\Company;
use Domain\Surveys\Actions\UpdateSurveyAction;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UpdateSurveyAction Integration Tests', function () {

    beforeEach(function () {
        $this->action = new UpdateSurveyAction();
        $this->company = Company::factory()->create();
        $this->survey = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'status' => SurveyStatus::ACTIVE,
        ]);
    });

    it('updates a survey with complete data', function () {
        $updateData = [
            'title' => 'Updated Survey Title',
            'description' => 'Updated survey description',
            'status' => SurveyStatus::COMPLETED,
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey)
            ->toBeInstanceOf(Survey::class)
            ->and($updatedSurvey->id)->toBe($this->survey->id) // Same survey
            ->and($updatedSurvey->company_id)->toBe($this->company->id) // Company unchanged
            ->and($updatedSurvey->title)->toBe('Updated Survey Title')
            ->and($updatedSurvey->description)->toBe('Updated survey description')
            ->and($updatedSurvey->status)->toBe(SurveyStatus::COMPLETED);

        // Verify it's actually updated in the database
        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'company_id' => $this->company->id,
            'title' => 'Updated Survey Title',
            'description' => 'Updated survey description',
            'status' => SurveyStatus::COMPLETED->value,
        ]);
    });

    it('updates a survey with partial data (title only)', function () {
        $updateData = [
            'title' => 'Only Title Updated',
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey)
            ->toBeInstanceOf(Survey::class)
            ->and($updatedSurvey->id)->toBe($this->survey->id)
            ->and($updatedSurvey->title)->toBe('Only Title Updated')
            ->and($updatedSurvey->description)->toBe('Original Description') // Unchanged
            ->and($updatedSurvey->status)->toBe(SurveyStatus::ACTIVE); // Unchanged

        // Verify database reflects partial update
        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'title' => 'Only Title Updated',
            'description' => 'Original Description',
            'status' => SurveyStatus::ACTIVE->value,
        ]);
    });

    it('updates a survey with partial data (description only)', function () {
        $updateData = [
            'description' => 'Only description was updated',
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey)
            ->and($updatedSurvey->title)->toBe('Original Title') // Unchanged
            ->and($updatedSurvey->description)->toBe('Only description was updated')
            ->and($updatedSurvey->status)->toBe(SurveyStatus::ACTIVE); // Unchanged
    });

    it('updates a survey with partial data (status only)', function () {
        $updateData = [
            'status' => SurveyStatus::COMPLETED,
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey)
            ->and($updatedSurvey->title)->toBe('Original Title') // Unchanged
            ->and($updatedSurvey->description)->toBe('Original Description') // Unchanged
            ->and($updatedSurvey->status)->toBe(SurveyStatus::COMPLETED);
    });

    it('can update description to null', function () {
        $updateData = [
            'description' => null,
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey->description)->toBeNull();

        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'description' => null,
        ]);
    });

    it('preserves company_id when updating other fields', function () {
        $anotherCompany = Company::factory()->create();

        $updateData = [
            'company_id' => $anotherCompany->id,
            'title' => 'New Title',
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey->company_id)->toBe($anotherCompany->id)
            ->and($updatedSurvey->title)->toBe('New Title');

        // Verify company_id can be updated if provided
        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'company_id' => $anotherCompany->id,
            'title' => 'New Title',
        ]);
    });

    it('returns fresh model instance with all current attributes', function () {
        $updateData = [
            'title' => 'Fresh Instance Test',
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        // Verify the returned instance has fresh data from database
        expect($updatedSurvey->title)->toBe('Fresh Instance Test')
            ->and($updatedSurvey->wasRecentlyCreated)->toBeFalse()
            ->and($updatedSurvey->exists)->toBeTrue()
            ->and($updatedSurvey->updated_at)->not->toBe($this->survey->updated_at);
    });

    it('handles empty update data gracefully', function () {
        $updateData = [];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        // Should return the same survey with no changes
        expect($updatedSurvey)
            ->and($updatedSurvey->id)->toBe($this->survey->id)
            ->and($updatedSurvey->title)->toBe('Original Title')
            ->and($updatedSurvey->description)->toBe('Original Description')
            ->and($updatedSurvey->status)->toBe(SurveyStatus::ACTIVE);
    });

    it('handles long text fields correctly', function () {
        $longTitle = str_repeat('Title ', 50); // ~250 chars
        $longDescription = str_repeat('Description text. ', 50); // ~900 chars

        $updateData = [
            'title' => $longTitle,
            'description' => $longDescription,
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey->title)->toBe($longTitle)
            ->and($updatedSurvey->description)->toBe($longDescription);
    });

    it('handles unicode characters correctly', function () {
        $updateData = [
            'title' => 'Survey Title with émojis 🎯 and accénts',
            'description' => 'Description with special characters: áéíóú ñ 中文',
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey->title)->toBe('Survey Title with émojis 🎯 and accénts')
            ->and($updatedSurvey->description)->toBe('Description with special characters: áéíóú ñ 中文');

        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'title' => 'Survey Title with émojis 🎯 and accénts',
            'description' => 'Description with special characters: áéíóú ñ 中文',
        ]);
    });

    it('maintains proper relationships after update', function () {
        $updateData = [
            'title' => 'Relationship Test',
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        // Verify relationships are maintained
        expect($updatedSurvey->company)->toBeInstanceOf(Company::class)
            ->and($updatedSurvey->company->id)->toBe($this->company->id)
            ->and($updatedSurvey->responses)->toBeEmpty()
            ->and($updatedSurvey->summary)->toBeNull();
    });

    it('handles status transitions correctly', function () {
        // Test ACTIVE -> COMPLETED
        $activeToCompleted = $this->action->execute($this->survey, ['status' => SurveyStatus::COMPLETED]);
        expect($activeToCompleted->status)->toBe(SurveyStatus::COMPLETED);

        // Test COMPLETED -> ACTIVE
        $completedToActive = $this->action->execute($activeToCompleted, ['status' => SurveyStatus::ACTIVE]);
        expect($completedToActive->status)->toBe(SurveyStatus::ACTIVE);
    });

    it('preserves timestamps and updates updated_at', function () {
        $originalCreatedAt = $this->survey->created_at;
        $originalUpdatedAt = $this->survey->updated_at;

        // Small delay to ensure updated_at changes
        sleep(1);

        $updateData = [
            'title' => 'Timestamp Test',
        ];

        $updatedSurvey = $this->action->execute($this->survey, $updateData);

        expect($updatedSurvey->created_at)->toEqual($originalCreatedAt) // Should not change
            ->and($updatedSurvey->updated_at)->not->toEqual($originalUpdatedAt); // Should change
    });

    it('can update multiple surveys independently', function () {
        $survey2 = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Second Survey',
            'status' => SurveyStatus::ACTIVE,
        ]);

        // Update first survey
        $updated1 = $this->action->execute($this->survey, ['title' => 'First Updated']);

        // Update second survey
        $updated2 = $this->action->execute($survey2, ['title' => 'Second Updated']);

        expect($updated1->title)->toBe('First Updated')
            ->and($updated2->title)->toBe('Second Updated')
            ->and($updated1->id)->not->toBe($updated2->id);

        // Verify both are updated in database
        $this->assertDatabaseHas('surveys', ['id' => $this->survey->id, 'title' => 'First Updated']);
        $this->assertDatabaseHas('surveys', ['id' => $survey2->id, 'title' => 'Second Updated']);
    });

    it('works with surveys from different companies', function () {
        $company2 = Company::factory()->create();
        $survey2 = Survey::factory()->create([
            'company_id' => $company2->id,
            'title' => 'Company 2 Survey',
        ]);

        $updated1 = $this->action->execute($this->survey, ['title' => 'Company 1 Updated']);
        $updated2 = $this->action->execute($survey2, ['title' => 'Company 2 Updated']);

        expect($updated1->company_id)->toBe($this->company->id)
            ->and($updated2->company_id)->toBe($company2->id)
            ->and($updated1->title)->toBe('Company 1 Updated')
            ->and($updated2->title)->toBe('Company 2 Updated');
    });
});

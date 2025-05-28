<?php

use Domain\Companies\Models\Company;
use Domain\Surveys\Actions\UpdateSurveyAction;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
        ->and($updatedSurvey->id)->toBe($this->survey->id)
        ->and($updatedSurvey->title)->toBe('Updated Survey Title')
        ->and($updatedSurvey->description)->toBe('Updated survey description')
        ->and($updatedSurvey->status)->toBe(SurveyStatus::COMPLETED);
});

it('updates a survey with partial data', function () {
    $updateData = [
        'title' => 'Only Title Updated',
    ];

    $updatedSurvey = $this->action->execute($this->survey, $updateData);

    expect($updatedSurvey)
        ->and($updatedSurvey->title)->toBe('Only Title Updated')
        ->and($updatedSurvey->description)->toBe('Original Description') // Unchanged
        ->and($updatedSurvey->status)->toBe(SurveyStatus::ACTIVE); // Unchanged
});

it('can update description to null', function () {
    $updateData = [
        'description' => null,
    ];

    $updatedSurvey = $this->action->execute($this->survey, $updateData);

    expect($updatedSurvey->description)->toBeNull()
        ->and($updatedSurvey->title)->toBe('Original Title') // Unchanged
        ->and($updatedSurvey->status)->toBe(SurveyStatus::ACTIVE); // Unchanged
});

it('can update title to empty string', function () {
    $updateData = [
        'title' => '',
    ];

    $updatedSurvey = $this->action->execute($this->survey, $updateData);

    expect($updatedSurvey->title)->toBe('')
        ->and($updatedSurvey->description)->toBe('Original Description') // Unchanged
        ->and($updatedSurvey->status)->toBe(SurveyStatus::ACTIVE); // Unchanged
});

it('can update title to whitespace-only string', function () {
    $updateData = [
        'title' => '   ',
    ];

    $updatedSurvey = $this->action->execute($this->survey, $updateData);

    expect($updatedSurvey->title)->toBe('   ')
        ->and($updatedSurvey->description)->toBe('Original Description') // Unchanged
        ->and($updatedSurvey->status)->toBe(SurveyStatus::ACTIVE); // Unchanged
});

it('handles empty update data gracefully', function () {
    $updateData = [];

    $updatedSurvey = $this->action->execute($this->survey, $updateData);

    expect($updatedSurvey->title)->toBe('Original Title')
        ->and($updatedSurvey->description)->toBe('Original Description')
        ->and($updatedSurvey->status)->toBe(SurveyStatus::ACTIVE);
});

it('can update company_id', function () {
    $newCompany = Company::factory()->create();
    $updateData = [
        'company_id' => $newCompany->id,
    ];

    $updatedSurvey = $this->action->execute($this->survey, $updateData);

    expect($updatedSurvey->company_id)->toBe($newCompany->id)
        ->and($updatedSurvey->title)->toBe('Original Title') // Unchanged
        ->and($updatedSurvey->description)->toBe('Original Description'); // Unchanged
});

it('handles status transitions correctly', function () {
    // Test ACTIVE -> COMPLETED
    $activeToCompleted = $this->action->execute($this->survey, ['status' => SurveyStatus::COMPLETED]);
    expect($activeToCompleted->status)->toBe(SurveyStatus::COMPLETED);

    // Test COMPLETED -> ACTIVE
    $completedToActive = $this->action->execute($activeToCompleted, ['status' => SurveyStatus::ACTIVE]);
    expect($completedToActive->status)->toBe(SurveyStatus::ACTIVE);
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

it('returns fresh model instance', function () {
    $updateData = [
        'title' => 'Fresh Instance Test',
    ];

    $updatedSurvey = $this->action->execute($this->survey, $updateData);

    // Verify the returned instance has fresh data from database
    expect($updatedSurvey->title)->toBe('Fresh Instance Test')
        ->and($updatedSurvey->wasRecentlyCreated)->toBeFalse()
        ->and($updatedSurvey->exists)->toBeTrue();
});

it('preserves relationships after update', function () {
    $updateData = [
        'title' => 'Relationship Test',
    ];

    $updatedSurvey = $this->action->execute($this->survey, $updateData);

    // Verify relationships are maintained
    expect($updatedSurvey->company)->toBeInstanceOf(Company::class)
        ->and($updatedSurvey->company->id)->toBe($this->company->id);
});

<?php

use Domain\Companies\Models\Company;
use Domain\Surveys\Actions\DeleteSurveyAction;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new DeleteSurveyAction;
    $this->company = Company::factory()->create();
    $this->survey = Survey::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Survey to Delete',
        'description' => 'This survey will be deleted',
        'status' => SurveyStatus::ACTIVE,
    ]);
});

it('soft deletes a survey', function () {
    $result = $this->action->execute($this->survey);

    expect($result)->toBeTrue()
        ->and(Survey::withTrashed()->find($this->survey->id))->not->toBeNull()
        ->and(Survey::find($this->survey->id))->toBeNull()
        ->and(Survey::withTrashed()->find($this->survey->id)->deleted_at)->not->toBeNull();
});

it('returns true when soft delete is successful', function () {
    $result = $this->action->execute($this->survey);

    expect($result)->toBeTrue();
});

it('can soft delete already completed survey', function () {
    $this->survey->update(['status' => SurveyStatus::COMPLETED]);

    $result = $this->action->execute($this->survey);

    expect($result)->toBeTrue()
        ->and(Survey::withTrashed()->find($this->survey->id)->status)->toBe(SurveyStatus::COMPLETED)
        ->and(Survey::withTrashed()->find($this->survey->id)->deleted_at)->not->toBeNull();
});

it('preserves original survey data when soft deleted', function () {
    $originalTitle = $this->survey->title;
    $originalDescription = $this->survey->description;
    $originalCompanyId = $this->survey->company_id;

    $this->action->execute($this->survey);

    $deletedSurvey = Survey::withTrashed()->find($this->survey->id);
    expect($deletedSurvey->title)->toBe($originalTitle)
        ->and($deletedSurvey->description)->toBe($originalDescription)
        ->and($deletedSurvey->company_id)->toBe($originalCompanyId);
});

it('soft delete does not affect other surveys', function () {
    $otherSurvey = Survey::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Other Survey',
    ]);

    $this->action->execute($this->survey);

    expect(Survey::find($otherSurvey->id))->not->toBeNull()
        ->and(Survey::count())->toBe(1);
});

it('can be executed multiple times on same survey', function () {
    // First deletion
    $result1 = $this->action->execute($this->survey);
    expect($result1)->toBeTrue();

    // Second deletion (should still work with soft deleted model)
    $softDeletedSurvey = Survey::withTrashed()->find($this->survey->id);
    $result2 = $this->action->execute($softDeletedSurvey);
    expect($result2)->toBeTrue();
});

it('maintains relationships after soft delete', function () {
    $this->action->execute($this->survey);

    $deletedSurvey = Survey::withTrashed()->with('company')->find($this->survey->id);
    expect($deletedSurvey->company)->not->toBeNull()
        ->and($deletedSurvey->company->id)->toBe($this->company->id);
});

it('soft deleted surveys are excluded from normal queries', function () {
    $activeSurvey = Survey::factory()->create(['company_id' => $this->company->id]);

    $this->action->execute($this->survey);

    expect(Survey::count())->toBe(1)
        ->and(Survey::first()->id)->toBe($activeSurvey->id);
});

it('soft deleted surveys can be found with withTrashed', function () {
    $this->action->execute($this->survey);

    expect(Survey::withTrashed()->count())->toBe(1)
        ->and(Survey::onlyTrashed()->count())->toBe(1)
        ->and(Survey::onlyTrashed()->first()->id)->toBe($this->survey->id);
});

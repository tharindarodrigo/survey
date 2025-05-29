<?php

use Domain\Companies\Models\Company;
use Domain\Surveys\Actions\IndexSurveysAction;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new IndexSurveysAction;
    $this->company = Company::factory()->create();
});

it('returns all active surveys', function () {
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

    $result = $this->action->execute();

    expect($result)->toHaveCount(2)
        ->and($result->pluck('id')->toArray())->toContain($survey1->id, $survey2->id);
});

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

    $result = $this->action->execute();

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($activeSurvey->id)
        ->and($result->pluck('id')->toArray())->not->toContain($deletedSurvey->id);
});

it('returns empty collection when no surveys exist', function () {
    $result = $this->action->execute();

    expect($result)->toHaveCount(0)
        ->and($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

it('returns surveys from different companies', function () {
    $company2 = Company::factory()->create();

    $survey1 = Survey::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Company 1 Survey',
    ]);

    $survey2 = Survey::factory()->create([
        'company_id' => $company2->id,
        'title' => 'Company 2 Survey',
    ]);

    $result = $this->action->execute();

    expect($result)->toHaveCount(2)
        ->and($result->pluck('company_id')->toArray())->toContain($this->company->id, $company2->id);
});

it('maintains survey relationships', function () {
    $survey = Survey::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Survey with Company',
    ]);

    $result = $this->action->execute();

    expect($result->first()->company_id)->toBe($this->company->id);
});

it('returns surveys in database order', function () {
    $firstSurvey = Survey::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'First Survey',
    ]);

    $secondSurvey = Survey::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Second Survey',
    ]);

    $result = $this->action->execute();

    expect($result->first()->id)->toBe($firstSurvey->id)
        ->and($result->last()->id)->toBe($secondSurvey->id);
});

it('handles mix of active and completed surveys', function () {
    Survey::factory()->create([
        'company_id' => $this->company->id,
        'status' => SurveyStatus::ACTIVE,
    ]);

    Survey::factory()->create([
        'company_id' => $this->company->id,
        'status' => SurveyStatus::COMPLETED,
    ]);

    $result = $this->action->execute();

    expect($result)->toHaveCount(2);

    $statuses = $result->pluck('status');
    expect($statuses)->toContain(SurveyStatus::ACTIVE, SurveyStatus::COMPLETED);
});

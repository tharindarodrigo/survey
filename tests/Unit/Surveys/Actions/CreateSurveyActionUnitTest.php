<?php

use Domain\Companies\Models\Company;
use Domain\Surveys\Actions\CreateSurveyAction;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new CreateSurveyAction;
    $this->company = Company::factory()->create();
});

it('creates a survey with correct data', function () {
    $data = [
        'company_id' => $this->company->id,
        'title' => 'How Satisfied Are You?',
        'description' => 'We value your feedback!',
    ];

    $survey = $this->action->execute($data);

    expect($survey)
        ->toBeInstanceOf(Survey::class)
        ->and($survey->company_id)->toBe($this->company->id)
        ->and($survey->title)->toBe('How Satisfied Are You?')
        ->and($survey->description)->toBe('We value your feedback!')
        ->and($survey->status)->toBe(SurveyStatus::ACTIVE);
});

it('creates a survey with minimal data', function () {
    $data = [
        'company_id' => $this->company->id,
        'title' => 'Minimal Survey',
    ];

    $survey = $this->action->execute($data);

    expect($survey)
        ->toBeInstanceOf(Survey::class)
        ->and($survey->company_id)->toBe($this->company->id)
        ->and($survey->title)->toBe('Minimal Survey')
        ->and($survey->description)->toBeNull()
        ->and($survey->status)->toBe(SurveyStatus::ACTIVE);
});

it('always sets status to active regardless of input', function () {
    $data = [
        'company_id' => $this->company->id,
        'title' => 'Status Test Survey',
        'status' => SurveyStatus::COMPLETED, // This should be overridden
    ];

    $survey = $this->action->execute($data);

    expect($survey->status)->toBe(SurveyStatus::ACTIVE);
});

it('requires company_id to create a survey', function () {
    $data = [
        'title' => 'Feedback Survey',
        'description' => 'Please provide your feedback.',
    ];

    $this->expectException(\Illuminate\Database\QueryException::class);

    $this->action->execute($data);
});

it('requires title to create a survey', function () {
    $data = [
        'company_id' => $this->company->id,
        'description' => 'Please provide your feedback.',
    ];

    $this->expectException(\Illuminate\Database\QueryException::class);

    $this->action->execute($data);
});

it('handles long text fields correctly', function () {
    $longTitle = str_repeat('A', 255); // Maximum allowed length
    $longDescription = str_repeat('B', 1000); // Maximum allowed length

    $data = [
        'company_id' => $this->company->id,
        'title' => $longTitle,
        'description' => $longDescription,
    ];

    $survey = $this->action->execute($data);

    expect($survey->title)->toBe($longTitle)
        ->and($survey->description)->toBe($longDescription)
        ->and(strlen($survey->title))->toBe(255)
        ->and(strlen($survey->description))->toBe(1000);
});

it('creates survey with proper relationships', function () {
    $data = [
        'company_id' => $this->company->id,
        'title' => 'Relationship Test Survey',
        'description' => 'Testing relationships',
    ];

    $survey = $this->action->execute($data);

    // Test the relationship
    expect($survey->company)
        ->toBeInstanceOf(Company::class)
        ->and($survey->company->id)->toBe($this->company->id);
});

it('returns fresh model instance with all attributes', function () {
    $data = [
        'company_id' => $this->company->id,
        'title' => 'Fresh Instance Test',
        'description' => 'Testing fresh instance',
    ];

    $survey = $this->action->execute($data);

    expect($survey->id)->toBeInt()
        ->and($survey->created_at)->not->toBeNull()
        ->and($survey->updated_at)->not->toBeNull()
        ->and($survey->deleted_at)->toBeNull();
});

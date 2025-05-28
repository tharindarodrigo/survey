<?php

use Domain\Companies\Models\Company;
use Domain\Surveys\Actions\CreateSurveyAction;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CreateSurveyAction Integration Tests', function () {

    beforeEach(function () {
        $this->action = new CreateSurveyAction();
        $this->company = Company::factory()->create();
    });

    it('creates a survey with complete data', function () {
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
            ->and($survey->status)->toBe(SurveyStatus::ACTIVE)
            ->and($survey->exists)->toBeTrue(); // Verify it's persisted

        // Verify it's actually in the database
        $this->assertDatabaseHas('surveys', [
            'id' => $survey->id,
            'company_id' => $this->company->id,
            'title' => 'How Satisfied Are You?',
            'description' => 'We value your feedback!',
            'status' => SurveyStatus::ACTIVE->value,
        ]);
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

        $this->assertDatabaseHas('surveys', [
            'company_id' => $this->company->id,
            'title' => 'Minimal Survey',
            'description' => null,
            'status' => SurveyStatus::ACTIVE->value,
        ]);
    });

    it('always sets status to active regardless of input', function () {
        $data = [
            'company_id' => $this->company->id,
            'title' => 'Status Test Survey',
            'status' => SurveyStatus::COMPLETED, // This should be overridden
        ];

        $survey = $this->action->execute($data);

        expect($survey->status)->toBe(SurveyStatus::ACTIVE);

        $this->assertDatabaseHas('surveys', [
            'company_id' => $this->company->id,
            'title' => 'Status Test Survey',
            'status' => SurveyStatus::ACTIVE->value,
        ]);
    });

    it('preserves all valid input data while setting status', function () {
        $data = [
            'company_id' => $this->company->id,
            'title' => 'Data Preservation Test',
            'description' => 'Testing data preservation',
            'status' => SurveyStatus::COMPLETED, // Should be overridden
        ];

        $survey = $this->action->execute($data);

        expect($survey)
            ->and($survey->company_id)->toBe($this->company->id)
            ->and($survey->title)->toBe('Data Preservation Test')
            ->and($survey->description)->toBe('Testing data preservation')
            ->and($survey->status)->toBe(SurveyStatus::ACTIVE); // Overridden
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

        // Verify inverse relationship
        $this->company->refresh();
        expect($this->company->surveys)->toHaveCount(1)
            ->and($this->company->surveys->first()->id)->toBe($survey->id);
    });

    it('creates multiple surveys for same company', function () {
        $surveys = [];

        for ($i = 1; $i <= 3; $i++) {
            $data = [
                'company_id' => $this->company->id,
                'title' => "Survey {$i}",
                'description' => "Description for survey {$i}",
            ];

            $surveys[] = $this->action->execute($data);
        }

        expect($surveys)->toHaveCount(3);

        // Verify all surveys belong to the same company
        foreach ($surveys as $survey) {
            expect($survey->company_id)->toBe($this->company->id);
        }

        // Verify database count
        expect(Survey::where('company_id', $this->company->id)->count())->toBe(3);
    });

    it('creates surveys for different companies', function () {
        $company2 = Company::factory()->create();

        $survey1 = $this->action->execute([
            'company_id' => $this->company->id,
            'title' => 'Company 1 Survey',
        ]);

        $survey2 = $this->action->execute([
            'company_id' => $company2->id,
            'title' => 'Company 2 Survey',
        ]);

        expect($survey1->company_id)->toBe($this->company->id)
            ->and($survey2->company_id)->toBe($company2->id);

        // Verify each company has exactly one survey
        expect(Survey::where('company_id', $this->company->id)->count())->toBe(1)
            ->and(Survey::where('company_id', $company2->id)->count())->toBe(1);
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

    it('handles unicode characters correctly', function () {
        $data = [
            'company_id' => $this->company->id,
            'title' => 'Survey with émojis 🎯 and ñoño characters',
            'description' => 'Description with special chars: áéíóú ñ 你好',
        ];

        $survey = $this->action->execute($data);

        expect($survey->title)->toBe('Survey with émojis 🎯 and ñoño characters')
            ->and($survey->description)->toBe('Description with special chars: áéíóú ñ 你好');

        $this->assertDatabaseHas('surveys', [
            'title' => 'Survey with émojis 🎯 and ñoño characters',
            'description' => 'Description with special chars: áéíóú ñ 你好',
        ]);
    });

    it('returns fresh model instance with all attributes', function () {
        $data = [
            'company_id' => $this->company->id,
            'title' => 'Fresh Instance Test',
            'description' => 'Testing fresh instance',
        ];

        $survey = $this->action->execute($data);

        // Verify it has all expected attributes
        expect($survey->id)->toBeInt()
            ->and($survey->created_at)->not->toBeNull()
            ->and($survey->updated_at)->not->toBeNull()
            ->and($survey->deleted_at)->toBeNull(); // Should not be soft deleted
    });
});

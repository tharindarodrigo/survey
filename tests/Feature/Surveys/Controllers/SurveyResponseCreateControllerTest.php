<?php

use Domain\Companies\Models\Company;
use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->survey = Survey::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Customer Feedback Survey',
        'description' => 'Tell us about your experience',
        'status' => SurveyStatus::ACTIVE,
    ]);
});

describe('Survey Response Creation', function () {

    it('creates a survey response with valid data', function () {
        $responseData = [
            'participant_email' => 'participant@example.com',
            'response_text' => 'This is my feedback on the survey.',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'survey_id',
                    'participant_email',
                    'response_text',
                    'created_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'survey_id' => $this->survey->id,
                    'participant_email' => 'participant@example.com',
                    'response_text' => 'This is my feedback on the survey.',
                ]
            ]);

        $this->assertDatabaseHas('survey_responses', [
            'survey_id' => $this->survey->id,
            'participant_email' => 'participant@example.com',
            'response_text' => 'This is my feedback on the survey.',
        ]);
    });

    it('allows public access without authentication', function () {
        $responseData = [
            'participant_email' => 'public@example.com',
            'response_text' => 'Public response without auth.',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(201);
    });

    it('handles non-existent survey', function () {
        $responseData = [
            'participant_email' => 'participant@example.com',
            'response_text' => 'This is my feedback.',
        ];

        $response = $this->postJson("/api/surveys/99999/responses", $responseData);

        $response->assertStatus(404);
    });
});

describe('Survey Response Validation', function () {

    it('requires participant_email', function () {
        $responseData = [
            'response_text' => 'This is my feedback.',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['participant_email'])
            ->assertJsonFragment([
                'participant_email' => ['The participant email field is required.']
            ]);
    });

    it('requires valid email format', function () {
        $responseData = [
            'participant_email' => 'invalid-email',
            'response_text' => 'This is my feedback.',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['participant_email'])
            ->assertJsonFragment([
                'participant_email' => ['The participant email field must be a valid email address.']
            ]);
    });

    it('requires response_text', function () {
        $responseData = [
            'participant_email' => 'participant@example.com',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['response_text'])
            ->assertJsonFragment([
                'response_text' => ['The response text field is required.']
            ]);
    });

    it('validates email length limit', function () {
        $longEmail = str_repeat('a', 250) . '@example.com'; // > 255 chars

        $responseData = [
            'participant_email' => $longEmail,
            'response_text' => 'This is my feedback.',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['participant_email']);
    });

    it('validates response_text length limit', function () {
        $longText = str_repeat('a', 10001); // > 10000 chars

        $responseData = [
            'participant_email' => 'participant@example.com',
            'response_text' => $longText,
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['response_text'])
            ->assertJsonFragment([
                'response_text' => ['The response text must not be greater than 10,000 characters.']
            ]);
    });

    it('allows maximum valid response_text length', function () {
        $maxText = str_repeat('a', 10000); // exactly 10000 chars

        $responseData = [
            'participant_email' => 'participant@example.com',
            'response_text' => $maxText,
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'response_text' => $maxText,
                ]
            ]);
    });
});

describe('Duplicate Response Prevention', function () {

    it('prevents duplicate responses from same email for same survey', function () {
        $responseData = [
            'participant_email' => 'participant@example.com',
            'response_text' => 'First response.',
        ];

        // First response should succeed
        $response1 = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);
        $response1->assertStatus(201);

        // Second response from same email should fail
        $responseData['response_text'] = 'Second response attempt.';
        $response2 = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['participant_email'])
            ->assertJsonFragment([
                'participant_email' => ['You have already submitted a response for this survey.']
            ]);

        // Verify only one response exists
        expect(SurveyResponse::where('survey_id', $this->survey->id)
            ->where('participant_email', 'participant@example.com')
            ->count())->toBe(1);
    });

    it('allows same email to respond to different surveys', function () {
        $survey2 = Survey::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Another Survey',
        ]);

        $responseData = [
            'participant_email' => 'participant@example.com',
            'response_text' => 'Response to first survey.',
        ];

        // Response to first survey
        $response1 = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);
        $response1->assertStatus(201);

        // Response to second survey should succeed
        $responseData['response_text'] = 'Response to second survey.';
        $response2 = $this->postJson("/api/surveys/{$survey2->id}/responses", $responseData);
        $response2->assertStatus(201);

        // Verify both responses exist
        expect(SurveyResponse::where('participant_email', 'participant@example.com')->count())->toBe(2);
    });

    it('allows different emails to respond to same survey', function () {
        $responseData1 = [
            'participant_email' => 'participant1@example.com',
            'response_text' => 'Response from participant 1.',
        ];

        $responseData2 = [
            'participant_email' => 'participant2@example.com',
            'response_text' => 'Response from participant 2.',
        ];

        $response1 = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData1);
        $response1->assertStatus(201);

        $response2 = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData2);
        $response2->assertStatus(201);

        // Verify both responses exist
        expect(SurveyResponse::where('survey_id', $this->survey->id)->count())->toBe(2);
    });
});

describe('Rate Limiting', function () {

    it('applies rate limiting to prevent abuse', function () {
        // Make 10 requests (should be within limit)
        for ($i = 1; $i <= 10; $i++) {
            $responseData = [
                'participant_email' => "participant{$i}@example.com",
                'response_text' => "Response {$i}.",
            ];

            $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);
            $response->assertStatus(201);
        }

        // 11th request should be rate limited
        $responseData = [
            'participant_email' => 'participant11@example.com',
            'response_text' => 'This should be rate limited.',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);
        $response->assertStatus(429); // Too Many Requests
    });
});

describe('Survey Status Validation', function () {

    it('allows responses to active surveys', function () {
        // Survey is already created as active in beforeEach
        $responseData = [
            'participant_email' => 'participant@example.com',
            'response_text' => 'Response to active survey.',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'survey_id' => $this->survey->id,
                    'participant_email' => 'participant@example.com',
                    'response_text' => 'Response to active survey.',
                ]
            ]);
    });

    it('prevents responses to completed surveys', function () {
        $this->survey->update(['status' => SurveyStatus::COMPLETED]);

        $responseData = [
            'participant_email' => 'participant@example.com',
            'response_text' => 'Response to completed survey.',
        ];

        $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This survey is not accepting responses.'
            ]);

        // Verify no response was created
        $this->assertDatabaseMissing('survey_responses', [
            'survey_id' => $this->survey->id,
            'participant_email' => 'participant@example.com',
        ]);
    });

    describe('Survey Response Edge Cases', function () {

        it('handles special characters in response text', function () {
            $responseData = [
                'participant_email' => 'participant@example.com',
                'response_text' => 'Response with émojis 🎯 and special chars: <>&"\' áéíóú',
            ];

            $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

            $response->assertStatus(201)
                ->assertJson([
                    'data' => [
                        'response_text' => 'Response with émojis 🎯 and special chars: <>&"\' áéíóú',
                    ]
                ]);
        });

        it('does not work with soft deleted surveys', function () {
            $this->survey->delete(); // Soft delete

            $responseData = [
                'participant_email' => 'participant@example.com',
                'response_text' => 'Response to deleted survey.',
            ];

            $response = $this->postJson("/api/surveys/{$this->survey->id}/responses", $responseData);

            $response->assertStatus(404);
        });
    });
});

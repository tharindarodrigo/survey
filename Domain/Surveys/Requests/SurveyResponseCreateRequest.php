<?php

namespace Domain\Surveys\Requests;

use Domain\Surveys\Enums\SurveyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyResponseCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $survey = $this->route('survey');

        // Only allow responses to active surveys
        return $survey && $survey->status === SurveyStatus::ACTIVE;
    }

    public function rules(): array
    {
        $survey = $this->route('survey');

        return [
            'participant_email' => [
                'required',
                'email',
                'max:255',
                // Ensure the same email can't submit multiple responses for the same survey
                Rule::unique('survey_responses')->where(function ($query) use ($survey) {
                    return $query->where('survey_id', $survey->id);
                }),
            ],
            'response_text' => ['required', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'participant_email.unique' => 'You have already submitted a response for this survey.',
            'response_text.max' => 'The response text must not be greater than 10,000 characters.',
        ];
    }

    /**
     * Get the error messages for authorization failures.
     */
    protected function failedAuthorization()
    {
        $survey = $this->route('survey');

        if (!$survey) {
            abort(404, 'Survey not found.');
        }

        if ($survey->status !== SurveyStatus::ACTIVE) {
            abort(403, 'This survey is not accepting responses.');
        }

        abort(403, 'Unauthorized.');
    }
}

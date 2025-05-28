<?php

namespace Domain\Surveys\Requests;

use Domain\Surveys\Enums\SurveyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['sometimes', 'exists:companies,id'],
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                // Ensure the combination of company_id and title is unique in surveys table, excluding soft-deleted records
                Rule::unique('surveys')->where(function ($query) {
                    $companyId = $this->input('company_id', $this->route('company_id'));
                    if ($companyId) {
                        $query->where('company_id', $companyId)
                            ->whereNull('deleted_at');
                    }
                })->ignore($this->route('survey')),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::enum(SurveyStatus::class)],
        ];
    }
}

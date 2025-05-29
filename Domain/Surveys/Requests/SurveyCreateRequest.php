<?php

namespace Domain\Surveys\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'title' => [
                'required',
                'string',
                'max:255',
                // Ensure the combination of company_id and title is unique in surveys table, excluding soft-deleted records
                Rule::unique('surveys')->where(function ($query) {
                    return $query->where('company_id', $this->input('company_id'))
                        ->whereNull('deleted_at');
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

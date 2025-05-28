<?php

namespace Domain\Surveys\Requests;

use Domain\Surveys\Enums\SurveyStatus;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Permissions\SurveyPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class SurveyCreateRequest extends FormRequest
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
            'company_id' => ['required', 'exists:companies,id'],
            'title' => [
                'required',
                'string',
                'max:255',
                // Ensure the combination of company_id and title is unique in surveys table
                Rule::unique('surveys')->where(function ($query) {
                    return $query->where('company_id', $this->input('company_id'));
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

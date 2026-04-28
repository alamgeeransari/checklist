<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'workflow_id' => ['required', 'integer', 'exists:workflows,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'release_tag' => ['nullable', 'string', 'max:255'],
        ];
    }
}

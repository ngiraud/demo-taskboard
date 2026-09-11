<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('manageMembers', $this->project());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->project();

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('team_members', 'user_id')->where('team_id', $project->team_id),
                Rule::unique('project_user', 'user_id')->where('project_id', $project->id),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => __('This user is not a member of the team.'),
            'user_id.unique' => __('This user is already a member of the project.'),
        ];
    }

    /**
     * Get the project associated with the request.
     */
    private function project(): Project
    {
        $project = $this->route('project');

        abort_if(! $project instanceof Project, 404);

        return $project;
    }
}

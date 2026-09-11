<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Store a newly created task in the project.
     */
    public function store(StoreTaskRequest $request, Team $currentTeam, Project $project): RedirectResponse
    {
        $project->tasks()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Move the task to another column.
     */
    public function update(UpdateTaskRequest $request, Team $currentTeam, Project $project, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return to_route('projects.show', $project);
    }
}

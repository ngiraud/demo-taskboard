<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display the projects of the current team the user is a member of.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        return Inertia::render('projects/Index', [
            'projects' => $request->user()->projects()
                ->whereBelongsTo($currentTeam)
                ->with('owner:id,name')
                ->withCount('tasks')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request, Team $currentTeam): RedirectResponse
    {
        $project = $currentTeam->projects()->create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
        ]);

        $project->members()->attach($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Display the kanban board of the project.
     *
     * The current team is resolved so that the project binding is scoped to it.
     */
    public function show(Team $currentTeam, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Show', [
            'project' => $project,
            'tasks' => $project->tasks()->with('assignee:id,name')->oldest('id')->get(),
            'members' => $project->members()->orderBy('name')->get(['users.id', 'users.name']),
            'statuses' => TaskStatus::options(),
        ]);
    }
}

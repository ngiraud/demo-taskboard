<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectMemberRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProjectMemberController extends Controller
{
    /**
     * Add a team member to the project.
     */
    public function store(StoreProjectMemberRequest $request, Team $currentTeam, Project $project): RedirectResponse
    {
        $project->members()->attach($request->validated('user_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member added.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Remove a member from the project and unassign their tasks.
     */
    public function destroy(Team $currentTeam, Project $project, User $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);

        abort_if($project->owner_id === $member->id, 403, __('The project owner cannot be removed.'));

        DB::transaction(function () use ($project, $member) {
            $project->tasks()->whereBelongsTo($member, 'assignee')->update(['assignee_id' => null]);
            $project->members()->detach($member);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('projects.show', $project);
    }
}

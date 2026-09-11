<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->hasMember($user);
    }

    /**
     * Determine whether the user can create and update the tasks of the project.
     */
    public function manageTasks(User $user, Project $project): bool
    {
        return $project->hasMember($user);
    }
}

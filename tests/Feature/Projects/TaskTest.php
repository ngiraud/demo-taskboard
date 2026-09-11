<?php

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

test('project members can create a task assigned to a project member', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->for($team)->for($user, 'owner')->create();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$team, $project]), [
            'title' => "Maquetter la page d'accueil",
            'description' => 'Sur Figma.',
            'assignee_id' => $user->id,
        ])
        ->assertRedirect(route('projects.show', [$team, $project]));

    $task = $project->tasks()->sole();

    expect($task->title)->toBe("Maquetter la page d'accueil")
        ->and($task->status)->toBe(TaskStatus::Todo)
        ->and($task->assignee_id)->toBe($user->id);
});

test('task title is required', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->for($team)->for($user, 'owner')->create();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$team, $project]), ['title' => ''])
        ->assertSessionHasErrors(['title' => 'The title field is required.']);

    expect($project->tasks()->exists())->toBeFalse();
});

test('tasks can only be assigned to project members', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->for($team)->for($user, 'owner')->create();
    $outsider = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$team, $project]), [
            'title' => 'Tâche',
            'assignee_id' => $outsider->id,
        ])
        ->assertSessionHasErrors(['assignee_id' => 'The selected assignee is invalid.']);

    expect($project->tasks()->exists())->toBeFalse();
});

test('users who are not project members cannot create tasks', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->for($team)->for($owner, 'owner')->create();

    $this->actingAs($member)
        ->post(route('projects.tasks.store', [$team, $project]), ['title' => 'Intrus'])
        ->assertForbidden();

    expect($project->tasks()->exists())->toBeFalse();
});

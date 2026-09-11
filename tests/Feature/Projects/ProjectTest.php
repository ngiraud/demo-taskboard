<?php

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $team = Team::factory()->create();

    $this->get(route('projects.index', $team))
        ->assertRedirect(route('login'));
});

test('users only see the projects of the current team they are a member of', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->for($team)->for($user, 'owner')->create();
    Project::factory()->for($team)->create();
    Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->get(route('projects.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/Index')
            ->has('projects', 1)
            ->where('projects.0.id', $project->id),
        );
});

test('users can create a project in their current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->actingAs($user)
        ->post(route('projects.store', $team), [
            'name' => 'Refonte du site',
            'description' => 'Le nouveau site vitrine.',
        ]);

    $project = $team->projects()->sole();

    $response->assertRedirect(route('projects.show', [$team, $project]));

    expect($project->name)->toBe('Refonte du site')
        ->and($project->owner_id)->toBe($user->id)
        ->and($project->members->modelKeys())->toBe([$user->id]);
});

test('project name is required', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->post(route('projects.store', $team), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);

    expect($team->projects()->exists())->toBeFalse();
});

test('users cannot create a project in a team they do not belong to', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store', $otherTeam), ['name' => 'Intrus'])
        ->assertForbidden();

    expect($otherTeam->projects()->exists())->toBeFalse();
});

test('project members can view the kanban board', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->for($team)->for($user, 'owner')->create();

    Task::factory()->for($project)->create([
        'status' => TaskStatus::InProgress,
        'assignee_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('projects.show', [$team, $project]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/Show')
            ->where('project.id', $project->id)
            ->has('tasks', 1)
            ->where('tasks.0.status', TaskStatus::InProgress->value)
            ->where('tasks.0.assignee.name', $user->name)
            ->has('members', 1)
            ->has('statuses', 3),
        );
});

test('team members who are not project members cannot view the project', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->for($team)->for($owner, 'owner')->create();

    $this->actingAs($member)
        ->get(route('projects.show', [$team, $project]))
        ->assertForbidden();
});

test('switching team on a project page redirects to the projects of the new team', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->for($user, 'owner')->create();
    $otherTeam = Team::factory()->create();

    $otherTeam->members()->attach($user, ['role' => TeamRole::Member->value]);
    $user->switchTeam($otherTeam);

    $this->actingAs($user)
        ->get(route('projects.show', [$otherTeam, $project]))
        ->assertRedirect(route('projects.index', $otherTeam));
});

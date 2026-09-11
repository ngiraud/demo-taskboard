<?php

use App\Models\Project;
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

    $this->actingAs($user)
        ->post(route('projects.store', $team), [
            'name' => 'Site du BDE',
            'description' => 'Le nouveau site du bureau des étudiants.',
        ])
        ->assertRedirect(route('projects.index', $team));

    $project = $team->projects()->sole();

    expect($project->name)->toBe('Site du BDE')
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

<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a token holder can list the tasks of a project they belong to', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->for($user, 'assignee')->create();

    Sanctum::actingAs($user);

    $this->getJson(route('api.projects.tasks.index', $project))
        ->assertOk()
        ->assertJsonPath('data.0.id', $task->id)
        ->assertJsonPath('data.0.assignee.name', $user->name);
});

test('a token holder can create a task', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->for($user, 'owner')->create();

    Sanctum::actingAs($user);

    $this->postJson(route('api.projects.tasks.store', $project), [
        'title' => 'Créée depuis la CI',
    ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Créée depuis la CI');

    expect($project->tasks()->sole()->title)->toBe('Créée depuis la CI');
});

test('the api rejects a request without a token', function () {
    $project = Project::factory()->create();

    $this->postJson(route('api.projects.tasks.store', $project), ['title' => 'Nope'])
        ->assertUnauthorized();
});

test('a token holder cannot create a task on a project they do not belong to', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson(route('api.projects.tasks.store', $project), ['title' => 'Nope'])
        ->assertForbidden();

    expect($project->tasks()->exists())->toBeFalse();
});

test('the api validates the payload', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->for($user, 'owner')->create();

    Sanctum::actingAs($user);

    $this->postJson(route('api.projects.tasks.store', $project), ['title' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

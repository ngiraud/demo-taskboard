<?php

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the project owner sees the team members who can be added', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $teammate = User::factory()->create();
    $team->members()->attach($teammate, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->get(route('projects.show', [$team, $project]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canManageMembers', true)
            ->has('availableMembers', 1)
            ->where('availableMembers.0.id', $teammate->id),
        );
});

test('the project owner can add a team member to the project', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $teammate = User::factory()->create();
    $team->members()->attach($teammate, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('projects.members.store', [$team, $project]), ['user_id' => $teammate->id])
        ->assertRedirect(route('projects.show', [$team, $project]));

    expect($project->hasMember($teammate))->toBeTrue();
});

test('users outside the team cannot be added to the project', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $outsider = User::factory()->create();
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('projects.members.store', [$team, $project]), ['user_id' => $outsider->id])
        ->assertSessionHasErrors(['user_id' => 'This user is not a member of the team.']);

    expect($project->hasMember($outsider))->toBeFalse();
});

test('a user cannot be added twice to the project', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('projects.members.store', [$team, $project]), ['user_id' => $owner->id])
        ->assertSessionHasErrors(['user_id' => 'This user is already a member of the project.']);
});

test('project members who are not the owner cannot add members', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $member = User::factory()->create();
    $teammate = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $team->members()->attach($teammate, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();
    $project->members()->attach($member);

    $this->actingAs($member)
        ->post(route('projects.members.store', [$team, $project]), ['user_id' => $teammate->id])
        ->assertForbidden();

    expect($project->hasMember($teammate))->toBeFalse();
});

test('the project owner can remove a member, which unassigns their tasks', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();
    $project->members()->attach($member);
    $task = Task::factory()->for($project)->create(['assignee_id' => $member->id]);

    $this->actingAs($owner)
        ->delete(route('projects.members.destroy', [$team, $project, $member]))
        ->assertRedirect(route('projects.show', [$team, $project]));

    expect($project->hasMember($member))->toBeFalse()
        ->and($task->fresh()->assignee_id)->toBeNull();
});

test('project members who are not the owner cannot remove members', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();
    $project->members()->attach($member);

    $this->actingAs($member)
        ->delete(route('projects.members.destroy', [$team, $project, $owner]))
        ->assertForbidden();

    expect($project->hasMember($owner))->toBeTrue();
});

test('the project owner cannot be removed', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->delete(route('projects.members.destroy', [$team, $project, $owner]))
        ->assertForbidden();

    expect($project->hasMember($owner))->toBeTrue();
});

test('removing a user who is not a project member returns a 404', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $teammate = User::factory()->create();
    $team->members()->attach($teammate, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->delete(route('projects.members.destroy', [$team, $project, $teammate]))
        ->assertNotFound();
});

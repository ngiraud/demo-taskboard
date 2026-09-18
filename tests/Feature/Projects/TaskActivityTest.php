<?php

use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Support\Facades\Notification;

test('creating a task records an activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->for($user, 'owner')->create();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$user->currentTeam, $project]), [
            'title' => 'Corriger le header',
        ]);

    $activity = $project->activities()->sole();

    expect($activity->type)->toBe(ActivityType::TaskCreated)
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->description)->toBe('created Corriger le header');
});

test('moving a task records the new status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['status' => TaskStatus::Todo]);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$user->currentTeam, $project, $task]), [
            'status' => TaskStatus::Done->value,
        ]);

    $activity = $project->activities()->where('type', ActivityType::TaskMoved)->sole();

    expect($activity->type)->toBe(ActivityType::TaskMoved)
        ->and($activity->description)->toBe("moved {$task->title} to Done");
});

test('the assignee is notified when a task lands on their plate', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->for($owner->currentTeam)->for($owner, 'owner')->create();
    $project->members()->attach($assignee);

    $this->actingAs($owner)
        ->post(route('projects.tasks.store', [$owner->currentTeam, $project]), [
            'title' => 'Corriger le header',
            'assignee_id' => $assignee->id,
        ]);

    Notification::assertSentTo($assignee, TaskAssigned::class);
});

test('assigning a task to yourself does not send a notification', function () {
    Notification::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->for($user, 'owner')->create();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$user->currentTeam, $project]), [
            'title' => 'Corriger le header',
            'assignee_id' => $user->id,
        ]);

    Notification::assertNothingSent();
});

test('the project page defers the activity timeline', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $url = route('projects.show', [$user->currentTeam, $project]);

    // The first visit draws the board without waiting for the timeline...
    $this->actingAs($user)
        ->get($url)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Show')
            ->has('tasks', 1)
            ->missing('activities'));

    // ...which Inertia then fetches in a follow up request, asking for that prop only.
    $this->actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'projects/Show',
            'X-Inertia-Partial-Data' => 'activities',
        ])
        ->get($url)
        ->assertOk()
        // Asserting on a field, not just a count: a resource envelope would slip
        // through a count assertion but break the component...
        ->assertJsonPath('props.activities.0.description', 'created '.$task->title)
        ->assertJsonPath('props.activities.0.author', 'Someone');
});

<?php

use App\Enums\ActivityType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * Post a slash command the way Slack does: a form encoded body, signed with the
 * shared secret. Passing a null secret produces a bogus signature.
 *
 * @param  array<string, string>  $payload
 */
function postSlackCommand(array $payload, ?string $secret = 'testing-secret', ?int $timestamp = null): TestResponse
{
    $payload = ['user_id' => 'U0DEMO', 'command' => '/task', ...$payload];
    $body = http_build_query($payload);
    $timestamp ??= time();

    return test()->call('POST', '/api/slack/tasks', $payload, [], [], [
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        'HTTP_X_SLACK_REQUEST_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SLACK_SIGNATURE' => $secret === null
            ? 'v0=deadbeef'
            : 'v0='.hash_hmac('sha256', 'v0:'.$timestamp.':'.$body, $secret),
    ], $body);
}

beforeEach(function () {
    config(['services.slack.signing_secret' => 'testing-secret']);

    $this->actor = User::factory()->create(['slack_user_id' => 'U0DEMO']);
    $this->project = Project::factory()
        ->for($this->actor->currentTeam)
        ->for($this->actor, 'owner')
        ->create(['name' => 'Refonte du site']);
});

test('a slash command creates a task on the matching project', function () {
    postSlackCommand(['text' => 'Refonte du site: corriger le header'])
        ->assertOk()
        ->assertJsonPath('response_type', 'in_channel');

    expect($this->project->tasks()->sole()->title)->toBe('corriger le header');
});

test('a slash command can assign the task to a project member', function () {
    $alice = User::factory()->create(['name' => 'Alice Martin']);
    $this->project->members()->attach($alice);

    postSlackCommand(['text' => 'Refonte du site: corriger le header @alice'])->assertOk();

    $task = $this->project->tasks()->sole();

    expect($task->title)->toBe('corriger le header')
        ->and($task->assignee_id)->toBe($alice->id);
});

test('the task is attributed to the linked account', function () {
    postSlackCommand(['text' => 'Refonte du site: corriger le header'])->assertOk();

    $activity = $this->project->activities()->sole();

    expect($activity->type)->toBe(ActivityType::TaskCreated)
        ->and($activity->user_id)->toBe($this->actor->id)
        ->and($activity->payload['via'])->toBe('Slack');
});

test('an unlinked slack account is told how to get linked', function () {
    postSlackCommand(['text' => 'Refonte du site: corriger le header', 'user_id' => 'U0STRANGER'])
        ->assertOk()
        ->assertJsonPath('response_type', 'ephemeral')
        ->assertJsonFragment(['text' => 'Your Slack account (U0STRANGER) is not linked to a Taskboard account yet.']);

    expect($this->project->tasks()->exists())->toBeFalse();
});

test('an unknown project is reported back to the channel', function () {
    postSlackCommand(['text' => 'Projet inconnu: corriger le header'])
        ->assertOk()
        ->assertJsonPath('response_type', 'ephemeral');

    expect($this->project->tasks()->exists())->toBeFalse();
});

test('a request that is not signed by slack is rejected', function () {
    postSlackCommand(['text' => 'Refonte du site: corriger le header'], secret: null)
        ->assertForbidden();

    expect($this->project->tasks()->exists())->toBeFalse();
});

test('a replayed request is rejected', function () {
    postSlackCommand(['text' => 'Refonte du site: corriger le header'], timestamp: time() - 3600)
        ->assertForbidden();

    expect($this->project->tasks()->exists())->toBeFalse();
});

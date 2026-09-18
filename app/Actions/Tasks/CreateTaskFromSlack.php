<?php

namespace App\Actions\Tasks;

use App\Data\SlackTaskCommand;
use App\Exceptions\SlackCommandFailed;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class CreateTaskFromSlack
{
    /**
     * Create a task on behalf of the Slack user who typed the command.
     *
     * @throws SlackCommandFailed
     */
    public function handle(User $author, SlackTaskCommand $command): Task
    {
        // Searching within the author's own projects is the authorization: a project
        // they are not a member of simply does not exist as far as they are concerned...
        $project = $author->projects
            ->first(fn (Project $project) => Str::lower($project->name) === Str::lower($command->projectName));

        if ($project === null) {
            throw SlackCommandFailed::unknownProject($command->projectName);
        }

        // ...and the timeline reads the context to show where the task came from.
        Context::add('activity_via', 'Slack');

        return $project->tasks()->create([
            'title' => $command->title,
            'assignee_id' => $this->findAssignee($project, $command->assigneeName)?->id,
        ]);
    }

    /**
     * Match a mention such as "@alice" against the members of the project.
     */
    private function findAssignee(Project $project, ?string $name): ?User
    {
        if ($name === null) {
            return null;
        }

        return $project->members->first(fn (User $member) => Str::lower($member->name) === Str::lower($name)
            || Str::lower(Str::before($member->name, ' ')) === Str::lower($name));
    }
}

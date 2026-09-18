<?php

namespace App\Observers;

use App\Enums\ActivityType;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $this->record($task, ActivityType::TaskCreated);

        $this->notifyAssignee($task);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        if ($task->wasChanged('status')) {
            $this->record($task, ActivityType::TaskMoved, ['status' => $task->status->label()]);
        }

        if (! $task->wasChanged('assignee_id')) {
            return;
        }

        $assignee = $this->assignee($task);

        if ($assignee === null) {
            $this->record($task, ActivityType::TaskUnassigned);

            return;
        }

        $this->record($task, ActivityType::TaskAssigned, ['assignee' => $assignee->name]);

        $this->notifyAssignee($task);
    }

    /**
     * Record an activity on the project timeline.
     *
     * @param  array<string, string>  $payload
     */
    private function record(Task $task, ActivityType $type, array $payload = []): void
    {
        $task->project->activities()->create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'payload' => [
                'title' => $task->title,
                ...$payload,
                // Set by the Slack endpoint, so the timeline can tell where the task came from...
                'via' => Context::get('activity_via'),
            ],
        ]);
    }

    /**
     * Notify the assignee that the task landed on their plate.
     */
    private function notifyAssignee(Task $task): void
    {
        $assignee = $this->assignee($task);

        if ($assignee === null || $assignee->is(Auth::user())) {
            return;
        }

        $assignee->notify(new TaskAssigned($task));
    }

    /**
     * Get the assignee straight from the database, so the relation is never stale.
     */
    private function assignee(Task $task): ?User
    {
        return $task->assignee()->first();
    }
}

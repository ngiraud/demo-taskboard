<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $task_id
 * @property int|null $user_id
 * @property ActivityType $type
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $author_name
 * @property-read string $description
 * @property-read Project $project
 * @property-read Task|null $task
 * @property-read User|null $user
 */
#[Fillable(['task_id', 'user_id', 'type', 'payload'])]
class Activity extends Model
{
    /**
     * Get the project the activity belongs to.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task the activity refers to.
     *
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who caused the activity.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the name of the author, or a fallback when their account is gone.
     *
     * @return Attribute<string, never>
     */
    protected function authorName(): Attribute
    {
        // The foreign key is nulled when an account is deleted, so an id means a user...
        return Attribute::get(fn (): string => $this->user_id === null
            ? __('Someone')
            : $this->user->name);
    }

    /**
     * Build a human readable sentence describing the activity.
     *
     * @return Attribute<string, never>
     */
    protected function description(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->type) {
            ActivityType::TaskCreated => __('created :task', ['task' => $this->payloadValue('title')]),
            ActivityType::TaskMoved => __('moved :task to :status', [
                'task' => $this->payloadValue('title'),
                'status' => $this->payloadValue('status'),
            ]),
            ActivityType::TaskAssigned => __('assigned :task to :assignee', [
                'task' => $this->payloadValue('title'),
                'assignee' => $this->payloadValue('assignee'),
            ]),
            ActivityType::TaskUnassigned => __('unassigned :task', ['task' => $this->payloadValue('title')]),
        });
    }

    /**
     * Read a value from the payload, falling back to a placeholder.
     */
    private function payloadValue(string $key): string
    {
        $value = $this->payload[$key] ?? null;

        return is_string($value) ? $value : '—';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'payload' => 'array',
        ];
    }
}

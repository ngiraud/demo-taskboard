<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tasks\CreateTaskFromSlack;
use App\Data\SlackTaskCommand;
use App\Exceptions\SlackCommandFailed;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Create a task from a Slack slash command.
 *
 * Expected syntax: /task Project name: Task title @assignee
 */
class SlackTaskController extends Controller
{
    /**
     * Handle the incoming slash command.
     */
    public function __invoke(Request $request, CreateTaskFromSlack $createTask): JsonResponse
    {
        $command = SlackTaskCommand::parse((string) $request->input('text'));

        if ($command === null) {
            return $this->reply(__('Syntax: /task Project name: Task title @assignee'));
        }

        $slackUserId = (string) $request->input('user_id');
        $author = User::firstWhere('slack_user_id', $slackUserId);

        if ($author === null) {
            // Showing the id makes the account easy to link: php artisan slack:link ...
            return $this->reply(__('Your Slack account (:id) is not linked to a Taskboard account yet.', [
                'id' => $slackUserId,
            ]));
        }

        // The observer reads the authenticated user to attribute the activity...
        Auth::setUser($author);

        try {
            $task = $createTask->handle($author, $command);
        } catch (SlackCommandFailed $exception) {
            return $this->reply($exception->getMessage());
        }

        return $this->reply($this->confirmation($task), inChannel: true);
    }

    /**
     * Build the confirmation shown in the channel.
     */
    private function confirmation(Task $task): string
    {
        if ($task->assignee === null) {
            return __('✅ ":title" added to :project.', [
                'title' => $task->title,
                'project' => $task->project->name,
            ]);
        }

        return __('✅ ":title" added to :project and assigned to :assignee.', [
            'title' => $task->title,
            'project' => $task->project->name,
            'assignee' => $task->assignee->name,
        ]);
    }

    /**
     * Build the message Slack shows back. Only a success is shown to everyone.
     */
    private function reply(string $text, bool $inChannel = false): JsonResponse
    {
        return response()->json([
            'response_type' => $inChannel ? 'in_channel' : 'ephemeral',
            'text' => $text,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Link a Taskboard account to a Slack account.
 *
 * A slash command carries the sender's Slack id but not their email, so the two
 * have to be matched once, up front.
 */
class LinkSlackAccountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:link
                            {email : The email of the Taskboard account}
                            {slack_user_id : The Slack identifier, such as U012AB3CD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link a Taskboard account to a Slack account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));

        if ($user === null) {
            $this->components->error(sprintf('No user found for %s.', $this->argument('email')));

            return self::FAILURE;
        }

        $slackUserId = (string) $this->argument('slack_user_id');

        $alreadyLinked = User::where('slack_user_id', $slackUserId)->whereKeyNot($user->id)->first();

        if ($alreadyLinked !== null) {
            $this->components->error(sprintf('%s is already linked to %s.', $slackUserId, $alreadyLinked->email));

            return self::FAILURE;
        }

        $user->forceFill(['slack_user_id' => $slackUserId])->save();

        $this->components->info(sprintf('%s is now linked to %s.', $user->email, $slackUserId));

        return self::SUCCESS;
    }
}

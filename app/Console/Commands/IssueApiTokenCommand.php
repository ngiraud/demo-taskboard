<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Issue a personal access token, so the API can be demoed from a terminal.
 */
class IssueApiTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:token
                            {email : The email of the user the token belongs to}
                            {--name=demo : A label to recognise the token later}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue a Sanctum personal access token for a user';

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

        $token = $user->createToken((string) $this->option('name'));

        $this->components->info(sprintf('Token issued for %s. It is shown once, copy it now:', $user->name));
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}

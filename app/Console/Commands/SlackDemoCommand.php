<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Replay a Slack slash command against the application.
 *
 * Handy to rehearse the demo, and as a fallback if Slack is unreachable on the day.
 */
class SlackDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:demo
                            {text : The text as typed after the slash command}
                            {--url= : The base URL of the target application}
                            {--user= : The Slack identifier of the sender, defaults to the seeded one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a signed Slack slash command to the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $secret = config('services.slack.signing_secret');

        if (! is_string($secret) || $secret === '') {
            $this->components->error('Set SLACK_SIGNING_SECRET before running this command.');

            return self::FAILURE;
        }

        $url = rtrim((string) ($this->option('url') ?: config('app.url')), '/').'/api/slack/tasks';

        $body = http_build_query([
            'token' => 'demo',
            'command' => '/task',
            'text' => $this->argument('text'),
            'user_id' => (string) ($this->option('user') ?: config('services.slack.demo_user_id')),
            'user_name' => 'demo',
            'channel_name' => 'general',
        ]);

        $timestamp = (string) time();
        $signature = 'v0='.hash_hmac('sha256', 'v0:'.$timestamp.':'.$body, $secret);

        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-Slack-Request-Timestamp' => $timestamp,
            'X-Slack-Signature' => $signature,
        ])->withBody($body, 'application/x-www-form-urlencoded')->post($url);

        $this->components->twoColumnDetail('POST '.$url, (string) $response->status());
        $this->line($response->body());

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}

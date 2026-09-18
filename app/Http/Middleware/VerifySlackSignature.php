<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify that an incoming slash command really comes from Slack.
 *
 * Slack signs every request with a shared secret. We rebuild the signature from
 * the raw body and compare it in constant time.
 *
 * @see https://api.slack.com/authentication/verifying-requests-from-slack
 */
class VerifySlackSignature
{
    /**
     * The number of seconds after which a signed request is considered a replay.
     */
    private const TOLERANCE = 300;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.slack.signing_secret');

        abort_if(! is_string($secret) || $secret === '', 503, 'The Slack integration is not configured.');

        $timestamp = $request->header('X-Slack-Request-Timestamp', '');
        $signature = $request->header('X-Slack-Signature', '');

        abort_if(abs(time() - (int) $timestamp) > self::TOLERANCE, 403, 'The Slack request has expired.');

        $expected = 'v0='.hash_hmac('sha256', 'v0:'.$timestamp.':'.$request->getContent(), $secret);

        abort_if(! hash_equals($expected, $signature), 403, 'The Slack signature is invalid.');

        return $next($request);
    }
}

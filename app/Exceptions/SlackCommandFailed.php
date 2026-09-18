<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a slash command is well formed but cannot be carried out.
 *
 * The message is written to be shown back in the Slack channel.
 */
class SlackCommandFailed extends Exception
{
    /**
     * The project name does not match any project the author belongs to.
     */
    public static function unknownProject(string $name): self
    {
        return new self(__('No project named ":name" was found.', ['name' => $name]));
    }
}

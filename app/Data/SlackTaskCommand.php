<?php

namespace App\Data;

use Illuminate\Support\Str;

/**
 * The text typed after a slash command, once parsed.
 *
 * Expected syntax: Project name: Task title @assignee
 */
readonly class SlackTaskCommand
{
    public function __construct(
        public string $projectName,
        public string $title,
        public ?string $assigneeName = null,
    ) {
        //
    }

    /**
     * Parse the raw text, or return null when it does not follow the syntax.
     */
    public static function parse(string $text): ?self
    {
        $text = trim($text);

        if (! str_contains($text, ':')) {
            return null;
        }

        [$projectName, $remainder] = explode(':', $text, 2);

        $projectName = trim($projectName);
        $remainder = trim($remainder);
        $assigneeName = null;

        if (preg_match('/@([\p{L}\-]+)\s*$/u', $remainder, $matches) === 1) {
            $assigneeName = $matches[1];
            $remainder = trim(Str::beforeLast($remainder, '@'.$assigneeName));
        }

        if ($projectName === '' || $remainder === '') {
            return null;
        }

        return new self($projectName, $remainder, $assigneeName);
    }
}

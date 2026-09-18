<?php

use App\Data\SlackTaskCommand;

test('it parses a project, a title and an assignee', function () {
    $command = SlackTaskCommand::parse('Refonte du site: Corriger le header @alice');

    expect($command)->not->toBeNull()
        ->and($command->projectName)->toBe('Refonte du site')
        ->and($command->title)->toBe('Corriger le header')
        ->and($command->assigneeName)->toBe('alice');
});

test('the assignee is optional', function () {
    $command = SlackTaskCommand::parse('Refonte du site: Corriger le header');

    expect($command->assigneeName)->toBeNull()
        ->and($command->title)->toBe('Corriger le header');
});

test('a title may contain an email like word without being mistaken for a mention', function () {
    $command = SlackTaskCommand::parse('Refonte du site: Écrire à contact@ngiraud.me');

    expect($command->title)->toBe('Écrire à contact@ngiraud.me')
        ->and($command->assigneeName)->toBeNull();
});

test('accented and hyphenated first names are supported', function () {
    $command = SlackTaskCommand::parse('Refonte du site: Relire la page @Jean-Élie');

    expect($command->assigneeName)->toBe('Jean-Élie')
        ->and($command->title)->toBe('Relire la page');
});

test('a colon inside the title is kept', function () {
    $command = SlackTaskCommand::parse('Refonte du site: Revoir le SEO: balises title');

    expect($command->projectName)->toBe('Refonte du site')
        ->and($command->title)->toBe('Revoir le SEO: balises title');
});

test('it rejects a text that does not follow the syntax', function (string $text) {
    expect(SlackTaskCommand::parse($text))->toBeNull();
})->with([
    'no colon' => 'Corriger le header',
    'no title' => 'Refonte du site:',
    'no project' => ': Corriger le header',
    'only a mention' => 'Refonte du site: @alice',
    'empty' => '',
]);

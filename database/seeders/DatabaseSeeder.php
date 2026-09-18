<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $john = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $jane = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $alice = User::factory()->create(['name' => 'Alice Martin', 'email' => 'alice@example.com']);
        $bob = User::factory()->create(['name' => 'Bob Martin', 'email' => 'bob@example.com']);
        $eve = User::factory()->create(['name' => 'Eve Durand', 'email' => 'eve@example.com']);

        // The account the Slack slash command acts as, matched on the Slack identifier...
        $nicolas = User::factory()->create([
            'name' => 'Nicolas Giraud',
            'email' => 'contact@ngiraud.me',
            'slack_user_id' => config('services.slack.demo_user_id'),
        ]);

        $team = Team::factory()->create(['name' => 'Studio Nova']);

        $team->members()->attach($john, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($jane, ['role' => TeamRole::Admin->value]);

        foreach ([$alice, $bob, $eve, $nicolas] as $member) {
            $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        }

        foreach ([$john, $jane, $alice, $bob, $eve, $nicolas] as $user) {
            $user->switchTeam($team);
        }

        $this->createProject($team, $john, 'Refonte du site', "Le nouveau site vitrine de l'agence.", [$jane, $alice, $nicolas], [
            ["Maquetter la page d'accueil", TaskStatus::Done, $jane],
            ['Mettre en place le SSO Google', TaskStatus::InProgress, $john],
            ['Intégrer le blog', TaskStatus::InProgress, $alice],
            ['Formulaire de contact', TaskStatus::Todo, null],
            ['Déployer sur Laravel Cloud', TaskStatus::Todo, $john],
        ]);

        $this->createProject($team, $jane, 'Application client', 'Suivi des commandes pour les clients B2B.', [$john, $bob], [
            ['Définir le modèle de données', TaskStatus::Done, $jane],
            ['API de suivi des commandes', TaskStatus::InProgress, $bob],
            ['Notifications de livraison', TaskStatus::Todo, $john],
        ]);

        $this->createProject($team, $alice, 'Maintenance clients', 'Mises à jour et correctifs des sites clients.', [$bob], [
            ['Passer les projets en PHP 8.5', TaskStatus::InProgress, $alice],
            ['Renouveler les certificats SSL', TaskStatus::Todo, $bob],
        ]);
    }

    /**
     * Create a project with its members and tasks.
     *
     * @param  array<int, User>  $members
     * @param  array<int, array{0: string, 1: TaskStatus, 2: User|null}>  $tasks
     */
    private function createProject(Team $team, User $owner, string $name, string $description, array $members, array $tasks): void
    {
        $project = Project::factory()
            ->for($team)
            ->for($owner, 'owner')
            ->create(['name' => $name, 'description' => $description]);

        $project->members()->attach(collect($members)->pluck('id'));

        foreach ($tasks as [$title, $status, $assignee]) {
            $project->tasks()->create([
                'title' => $title,
                'status' => $status,
                'assignee_id' => $assignee?->id,
            ]);
        }
    }
}

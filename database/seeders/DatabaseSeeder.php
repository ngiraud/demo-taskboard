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

        $team = Team::factory()->create(['name' => 'Promo 3A']);

        $team->members()->attach($john, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($jane, ['role' => TeamRole::Admin->value]);

        foreach ([$alice, $bob, $eve] as $member) {
            $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        }

        foreach ([$john, $jane, $alice, $bob, $eve] as $user) {
            $user->switchTeam($team);
        }

        $this->createProject($team, $john, 'Site du BDE', 'Le nouveau site du bureau des étudiants.', [$jane, $alice], [
            ["Maquetter la page d'accueil", TaskStatus::Done, $jane],
            ["Mettre en place l'authentification", TaskStatus::InProgress, $john],
            ['Page des événements', TaskStatus::InProgress, $alice],
            ["Formulaire d'adhésion", TaskStatus::Todo, null],
            ['Déployer sur Laravel Cloud', TaskStatus::Todo, $john],
        ]);

        $this->createProject($team, $jane, 'Appli covoiturage', 'Partager les trajets pour venir en cours.', [$john, $bob], [
            ['Définir le modèle de données', TaskStatus::Done, $jane],
            ['Recherche de trajets', TaskStatus::InProgress, $bob],
            ['Notifications de réservation', TaskStatus::Todo, $john],
        ]);

        $this->createProject($team, $alice, 'Hackathon 2026', "Organisation du hackathon de l'école.", [$bob], [
            ['Trouver des sponsors', TaskStatus::InProgress, $alice],
            ['Réserver les salles', TaskStatus::Todo, $bob],
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

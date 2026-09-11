<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FolderKanban, Plus } from '@lucide/vue';
import CreateProjectModal from '@/components/CreateProjectModal.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/projects';
import type { Project, Team } from '@/types';

defineProps<{
    projects: Project[];
    currentTeam: Team;
}>();

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            {
                title: 'Projects',
                href: index(props.currentTeam.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head title="Projects" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Projects"
                :description="`Your projects in ${currentTeam.name}`"
            />

            <CreateProjectModal :team="currentTeam">
                <Button> <Plus /> New project </Button>
            </CreateProjectModal>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="project in projects"
                :key="project.id"
                class="flex flex-col gap-2 rounded-xl border p-4"
            >
                <div class="flex items-center gap-2 font-medium">
                    <FolderKanban class="text-muted-foreground size-4" />
                    {{ project.name }}
                </div>

                <p class="text-muted-foreground line-clamp-2 text-sm">
                    {{ project.description }}
                </p>

                <div
                    class="text-muted-foreground mt-auto flex justify-between pt-2 text-xs"
                >
                    <span>{{ project.tasks_count }} tasks</span>
                    <span>Owner: {{ project.owner?.name }}</span>
                </div>
            </div>
        </div>

        <p
            v-if="projects.length === 0"
            class="text-muted-foreground py-8 text-center"
        >
            No projects yet. Create the first one!
        </p>
    </div>
</template>

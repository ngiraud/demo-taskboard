<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TaskCard from '@/components/TaskCard.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { index, show } from '@/routes/projects';
import type {
    Project,
    ProjectMember,
    StatusOption,
    Task,
    TaskStatus,
    Team,
} from '@/types';

const props = defineProps<{
    project: Project;
    tasks: Task[];
    members: ProjectMember[];
    statuses: StatusOption[];
    currentTeam: Team;
}>();

defineOptions({
    layout: (props: { currentTeam: Team; project: Project }) => ({
        breadcrumbs: [
            {
                title: 'Projects',
                href: index(props.currentTeam.slug),
            },
            {
                title: props.project.name,
                href: show([props.currentTeam.slug, props.project.id]),
            },
        ],
    }),
});

const { getInitials } = useInitials();

const tasksByStatus = (status: TaskStatus) =>
    props.tasks.filter((task) => task.status === status);
</script>

<template>
    <Head :title="project.name" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="project.name"
                :description="project.description ?? undefined"
            />

            <div class="flex -space-x-2">
                <Avatar
                    v-for="member in members"
                    :key="member.id"
                    :title="member.name"
                    class="border-background size-8 border-2"
                >
                    <AvatarFallback class="text-xs">
                        {{ getInitials(member.name) }}
                    </AvatarFallback>
                </Avatar>
            </div>
        </div>

        <div class="grid flex-1 items-start gap-4 md:grid-cols-3">
            <section
                v-for="status in statuses"
                :key="status.value"
                class="bg-muted/50 flex flex-col gap-3 rounded-xl p-3"
            >
                <h2
                    class="flex items-center justify-between px-1 text-sm font-medium"
                >
                    {{ status.label }}
                    <span class="text-muted-foreground">
                        {{ tasksByStatus(status.value).length }}
                    </span>
                </h2>

                <TaskCard
                    v-for="task in tasksByStatus(status.value)"
                    :key="task.id"
                    :task="task"
                />
            </section>
        </div>
    </div>
</template>

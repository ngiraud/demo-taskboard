<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import CreateTaskModal from '@/components/CreateTaskModal.vue';
import Heading from '@/components/Heading.vue';
import ProjectMembers from '@/components/ProjectMembers.vue';
import TaskCard from '@/components/TaskCard.vue';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/projects';
import { update } from '@/routes/projects/tasks';
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
    availableMembers: ProjectMember[];
    canManageMembers: boolean;
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

const tasksByStatus = (status: TaskStatus) =>
    props.tasks.filter((task) => task.status === status);

const draggedTaskId = ref<number | null>(null);
const hoveredStatus = ref<TaskStatus | null>(null);

function startDrag(event: DragEvent, task: Task) {
    draggedTaskId.value = task.id;
    event.dataTransfer?.setData('text/plain', String(task.id));
}

function moveTask(status: TaskStatus) {
    const movedTask = props.tasks.find(
        (task) => task.id === draggedTaskId.value,
    );

    draggedTaskId.value = null;
    hoveredStatus.value = null;

    if (!movedTask || movedTask.status === status) {
        return;
    }

    router
        .optimistic<{ tasks: Task[] }>((pageProps) => ({
            tasks: pageProps.tasks.map((task) =>
                task.id === movedTask.id ? { ...task, status } : task,
            ),
        }))
        .visit(
            update.patch([
                props.currentTeam.slug,
                props.project.id,
                movedTask.id,
            ]),
            {
                data: { status },
                preserveScroll: true,
            },
        );
}
</script>

<template>
    <Head :title="project.name" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="project.name"
                :description="project.description ?? undefined"
            />

            <div class="flex items-center gap-4">
                <ProjectMembers
                    :team="currentTeam"
                    :project="project"
                    :members="members"
                    :available-members="availableMembers"
                    :can-manage-members="canManageMembers"
                />

                <CreateTaskModal
                    :team="currentTeam"
                    :project="project"
                    :members="members"
                >
                    <Button> <Plus /> New task </Button>
                </CreateTaskModal>
            </div>
        </div>

        <div class="grid flex-1 gap-4 md:grid-cols-3">
            <section
                v-for="status in statuses"
                :key="status.value"
                class="flex min-h-64 flex-col gap-3 rounded-xl p-3 transition-colors"
                :class="
                    hoveredStatus === status.value ? 'bg-muted' : 'bg-muted/50'
                "
                @dragover.prevent="hoveredStatus = status.value"
                @dragleave="hoveredStatus = null"
                @drop="moveTask(status.value)"
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
                    draggable="true"
                    class="cursor-grab active:cursor-grabbing"
                    @dragstart="startDrag($event, task)"
                />
            </section>
        </div>
    </div>
</template>

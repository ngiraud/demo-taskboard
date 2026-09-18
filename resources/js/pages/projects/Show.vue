<script setup lang="ts">
import { Deferred, Head, router, usePoll } from '@inertiajs/vue3';
import { Pause, Play, Plus } from '@lucide/vue';
import { ref } from 'vue';
import ActivityFeed from '@/components/ActivityFeed.vue';
import CreateTaskModal from '@/components/CreateTaskModal.vue';
import Heading from '@/components/Heading.vue';
import ProjectMembers from '@/components/ProjectMembers.vue';
import TaskCard from '@/components/TaskCard.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { index, show } from '@/routes/projects';
import { update } from '@/routes/projects/tasks';
import type {
    Activity,
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
    activities?: Activity[];
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

// Ask the server for the board and the timeline every few seconds. A task created
// from Slack shows up on its own, without a websocket in sight...
const { start, stop, polling } = usePoll(3000, {
    only: ['tasks', 'activities'],
});

const tasksByStatus = (status: TaskStatus) =>
    props.tasks.filter((task) => task.status === status);

const draggedTaskId = ref<number | null>(null);
const hoveredStatus = ref<TaskStatus | null>(null);

function startDrag(event: DragEvent, task: Task) {
    draggedTaskId.value = task.id;
    event.dataTransfer?.setData('text/plain', String(task.id));
}

function dropTask(status: TaskStatus) {
    const droppedTask = props.tasks.find(
        (task) => task.id === draggedTaskId.value,
    );

    draggedTaskId.value = null;
    hoveredStatus.value = null;

    if (droppedTask) {
        moveTask(droppedTask, status);
    }
}

const isLastColumn = (status: TaskStatus) =>
    props.statuses.at(-1)?.value === status;

// Called when a card emits "advance": the page knows the column order, the card does not.
function advanceTask(task: Task) {
    const index = props.statuses.findIndex(
        (status) => status.value === task.status,
    );
    const nextStatus = props.statuses[index + 1];

    if (nextStatus) {
        moveTask(task, nextStatus.value);
    }
}

function moveTask(movedTask: Task, status: TaskStatus) {
    if (movedTask.status === status) {
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
                <Button
                    variant="ghost"
                    size="icon"
                    :title="
                        polling ? 'Pause live updates' : 'Resume live updates'
                    "
                    @click="polling ? stop() : start()"
                >
                    <Pause v-if="polling" />
                    <Play v-else />
                </Button>

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
                @drop="dropTask(status.value)"
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
                    :can-advance="!isLastColumn(status.value)"
                    draggable="true"
                    class="cursor-grab active:cursor-grabbing"
                    @dragstart="startDrag($event, task)"
                    @advance="advanceTask(task)"
                />
            </section>
        </div>

        <section class="mt-4 rounded-xl border p-4">
            <h2 class="mb-3 text-sm font-medium">Activity</h2>

            <Deferred data="activities">
                <template #fallback>
                    <div class="flex flex-col gap-2">
                        <Skeleton
                            v-for="line in 3"
                            :key="line"
                            class="h-5 w-full"
                        />
                    </div>
                </template>

                <template #default="{ reloading }">
                    <ActivityFeed
                        :activities="activities ?? []"
                        :class="{ 'opacity-50': reloading }"
                    />
                </template>
            </Deferred>
        </section>
    </div>
</template>

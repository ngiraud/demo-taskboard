<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { Task } from '@/types';

defineProps<{
    task: Task;
}>();

const { getInitials } = useInitials();
</script>

<template>
    <article
        class="bg-background flex flex-col gap-2 rounded-lg border p-3 shadow-xs"
    >
        <p class="text-sm font-medium">{{ task.title }}</p>

        <p
            v-if="task.description"
            class="text-muted-foreground line-clamp-2 text-xs"
        >
            {{ task.description }}
        </p>

        <div class="flex items-center justify-end">
            <div
                v-if="task.assignee"
                class="text-muted-foreground flex items-center gap-2 text-xs"
            >
                {{ task.assignee.name }}
                <Avatar class="size-6">
                    <AvatarFallback class="text-[10px]">
                        {{ getInitials(task.assignee.name) }}
                    </AvatarFallback>
                </Avatar>
            </div>
            <span v-else class="text-muted-foreground text-xs italic">
                Unassigned
            </span>
        </div>
    </article>
</template>

<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import type { Task } from '@/types';

defineProps<{
    task: Task;
    canAdvance?: boolean;
}>();

// The card does not know which column comes next, nor how to save the move.
// It only says "I want to move on", and the page decides what that means...
const emit = defineEmits<{
    advance: [];
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

        <div class="flex items-center justify-between gap-2">
            <div
                v-if="task.assignee"
                class="text-muted-foreground flex items-center gap-2 text-xs"
            >
                <Avatar class="size-6">
                    <AvatarFallback class="text-[10px]">
                        {{ getInitials(task.assignee.name) }}
                    </AvatarFallback>
                </Avatar>
                {{ task.assignee.name }}
            </div>
            <span v-else class="text-muted-foreground text-xs italic">
                Unassigned
            </span>

            <Button
                v-if="canAdvance"
                variant="ghost"
                size="icon"
                class="size-7"
                title="Move to the next column"
                @click="emit('advance')"
            >
                <ArrowRight />
            </Button>
        </div>
    </article>
</template>

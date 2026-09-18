<script setup lang="ts">
import { Hash } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { Activity } from '@/types';

defineProps<{
    activities: Activity[];
}>();
</script>

<template>
    <ul class="flex flex-col gap-1">
        <li
            v-for="activity in activities"
            :key="activity.id"
            class="flex items-center gap-2 text-sm"
        >
            <span class="font-medium">{{ activity.author }}</span>

            <span class="text-muted-foreground">{{
                activity.description
            }}</span>

            <Badge v-if="activity.via" variant="secondary" class="gap-1">
                <Hash class="size-3" />
                {{ activity.via }}
            </Badge>

            <span
                class="text-muted-foreground ml-auto text-xs"
                :title="activity.happened_on"
            >
                {{ activity.happened_at }}
            </span>
        </li>

        <li
            v-if="activities.length === 0"
            class="text-muted-foreground text-sm"
        >
            Nothing has happened on this project yet.
        </li>
    </ul>
</template>

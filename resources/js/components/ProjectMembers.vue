<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { UserPlus, X } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useInitials } from '@/composables/useInitials';
import { destroy, store } from '@/routes/projects/members';
import type { Project, ProjectMember, Team } from '@/types';

defineProps<{
    team: Team;
    project: Project;
    members: ProjectMember[];
    availableMembers: ProjectMember[];
    canManageMembers: boolean;
}>();

const { getInitials } = useInitials();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <button
                type="button"
                class="flex -space-x-2"
                title="Project members"
            >
                <Avatar
                    v-for="member in members"
                    :key="member.id"
                    class="border-background size-8 border-2"
                >
                    <AvatarFallback class="text-xs">
                        {{ getInitials(member.name) }}
                    </AvatarFallback>
                </Avatar>
            </button>
        </DialogTrigger>

        <DialogContent>
            <DialogHeader>
                <DialogTitle>Project members</DialogTitle>
                <DialogDescription>
                    Only members can see the project and be assigned to tasks.
                </DialogDescription>
            </DialogHeader>

            <ul class="space-y-2">
                <li
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center justify-between gap-4"
                >
                    <div class="flex items-center gap-3">
                        <Avatar class="size-8">
                            <AvatarFallback class="text-xs">
                                {{ getInitials(member.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <span class="text-sm font-medium">
                            {{ member.name }}
                        </span>
                        <Badge
                            v-if="member.id === project.owner_id"
                            variant="secondary"
                        >
                            Owner
                        </Badge>
                    </div>

                    <Button
                        v-if="
                            canManageMembers && member.id !== project.owner_id
                        "
                        variant="ghost"
                        size="sm"
                        as-child
                    >
                        <Link
                            :href="destroy([team.slug, project.id, member.id])"
                            as="button"
                            preserve-scroll
                        >
                            <X class="size-4" />
                        </Link>
                    </Button>
                </li>
            </ul>

            <template v-if="canManageMembers">
                <Form
                    v-if="availableMembers.length > 0"
                    v-bind="store.form([team.slug, project.id])"
                    class="flex items-start gap-2"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid flex-1 gap-2">
                        <select
                            name="user_id"
                            aria-label="Team member"
                            class="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                        >
                            <option
                                v-for="member in availableMembers"
                                :key="member.id"
                                :value="member.id"
                            >
                                {{ member.name }}
                            </option>
                        </select>
                        <InputError :message="errors.user_id" />
                    </div>

                    <Button type="submit" :disabled="processing">
                        <UserPlus /> Add
                    </Button>
                </Form>

                <p v-else class="text-muted-foreground text-sm">
                    Everyone in the team is already a member.
                </p>
            </template>
        </DialogContent>
    </Dialog>
</template>

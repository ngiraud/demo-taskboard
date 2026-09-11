<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/projects';
import type { Team } from '@/types';

defineProps<{
    team: Team;
}>();

const open = ref(false);
const formKey = ref(0);

function handleOpenChange(value: boolean) {
    open.value = value;

    if (!value) {
        formKey.value++;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <Form
                :key="formKey"
                v-bind="store.form(team.slug)"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle>Create a new project</DialogTitle>
                    <DialogDescription>
                        The project will belong to {{ team.name }}.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="name">Project name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="My project"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        name="description"
                        placeholder="What is this project about?"
                    />
                    <InputError :message="errors.description" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary"> Cancel </Button>
                    </DialogClose>

                    <Button type="submit" :disabled="processing">
                        Create project
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>

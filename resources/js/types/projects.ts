export type ProjectMember = {
    id: number;
    name: string;
};

export type Project = {
    id: number;
    name: string;
    description: string | null;
    owner_id: number;
    owner?: ProjectMember;
    tasks_count?: number;
};

export type TaskStatus = 'todo' | 'in_progress' | 'done';

export type StatusOption = {
    value: TaskStatus;
    label: string;
};

export type Task = {
    id: number;
    title: string;
    description: string | null;
    status: TaskStatus;
    assignee_id: number | null;
    assignee: ProjectMember | null;
};

export type Activity = {
    id: number;
    author: string;
    description: string;
    via: string | null;
    happened_at: string;
    happened_on: string;
};

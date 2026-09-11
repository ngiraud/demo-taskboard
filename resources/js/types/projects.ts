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

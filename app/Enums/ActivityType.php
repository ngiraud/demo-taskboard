<?php

namespace App\Enums;

enum ActivityType: string
{
    case TaskCreated = 'task_created';
    case TaskMoved = 'task_moved';
    case TaskAssigned = 'task_assigned';
    case TaskUnassigned = 'task_unassigned';
}

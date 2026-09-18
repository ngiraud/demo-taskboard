<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    /**
     * List the tasks of the project.
     *
     * @return AnonymousResourceCollection<int, TaskResource>
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        Gate::authorize('view', $project);

        return TaskResource::collection(
            $project->tasks()->with('assignee:id,name')->oldest('id')->get(),
        );
    }

    /**
     * Store a newly created task in the project.
     *
     * The very same form request as the web controller: authorization and
     * validation do not care which channel the request came from.
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $task = $project->tasks()->create($request->validated());

        // The status comes from a database default, so read the row back before serializing it...
        return TaskResource::make($task->refresh()->load('assignee:id,name'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}

<?php

use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Token authenticated API
|--------------------------------------------------------------------------
|
| These routes are stateless: no session, no cookie. The caller sends a
| personal access token in the Authorization header and Sanctum resolves
| the user from it. Everything else (policies, form requests) is shared
| with the web side.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', fn (Request $request) => $request->user())->name('api.user');

    Route::get('projects/{project}/tasks', [TaskController::class, 'index'])->name('api.projects.tasks.index');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('api.projects.tasks.store');
});

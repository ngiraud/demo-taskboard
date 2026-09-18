<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Everything is formatted here, so the front end only has to display it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author_name,
            'description' => $this->description,
            'via' => $this->payload['via'] ?? null,
            'happened_at' => $this->created_at?->diffForHumans(),
            'happened_on' => $this->created_at?->isoFormat('LLL'),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoachesPlayersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Customize the format of the user data here
        return [
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
        ];
    }
}

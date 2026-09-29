<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'review_id' => $this->review_id,

            'company' => [
                'company_id' => $this->company->company_id ?? null,
                'company_name' => $this->company->company_name ?? null,
            ],

            'user' => [
                'user_id' => $this->user->user_id ?? null,
                'full_name' => $this->user->full_name ?? null,
            ],

            'rating_life' => $this->rating_life,
            'rating_work' => $this->rating_work,
            'rating_money' => $this->rating_money,
            'rating_society' => $this->rating_society,

            'review_text' => $this->review_text,
            'status' => $this->status,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

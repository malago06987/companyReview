<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reviews = $this->reviews;

        return [
            'company_id' => $this->company_id,
            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->when(
                $request->user() && (
                    $request->user()->role === 'admin'
                    || $request->user()->user_id === $this->user_id
                ),
                $this->rejection_reason
            ),
            'submitted_by' => $this->when(
                $request->user()?->role === 'admin',
                $this->user_id
            ),
            'has_registration_document' => $this->when(
                $request->user()?->role === 'admin',
                $this->document !== null
            ),
            'company_name' => $this->company_name,
            'logo_image' => $this->logo_image,
            'description' => $this->description,
            'industry' => $this->industry->industry_name ?? null,
            'address' => $this->address,
            'benefits' => $this->benefits,
            'culture' => $this->culture,

            'rating' => [
                'life' => round($reviews->avg('rating_life'), 1),
                'work' => round($reviews->avg('rating_work'), 1),
                'money' => round($reviews->avg('rating_money'), 1),
                'society' => round($reviews->avg('rating_society'), 1),
                'overall' => round(
                    (
                        $reviews->avg('rating_life') +
                        $reviews->avg('rating_work') +
                        $reviews->avg('rating_money') +
                        $reviews->avg('rating_society')
                    ) / 4,
                    1
                ),
            ],

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

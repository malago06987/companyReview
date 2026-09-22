<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class companyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'company_id' => $this->company_id,
            'company_name' => $this->company_name,
            'logo_image' => $this->logo_image,
            'description' => $this->description,
            'industry' => $this->whenLoaded('industry', function () {
                return [
                    'industry_id' => $this->industry->industry_id,
                    'industry_name' => $this->industry->industry_name,
                ];
            }),
            'address' => $this->address,
            'benefits' => $this->benefits,
            'culture' => $this->culture,
            'reviews' => $this->whenLoaded('reviews', function () {
                return $this->reviews->map(fn ($review) => [
                    'review_id' => $review->review_id,
                    'rating_life' => $review->rating_life,
                    'rating_work' => $review->rating_work,
                    'rating_money' => $review->rating_money,
                    'rating_society' => $review->rating_society,
                    'review_text' => $review->review_text,
                    'status' => $review->status,
                ])->values();
            }),
            'jobs' => $this->whenLoaded('jobs', function () {
                return $this->jobs->map(fn ($job) => [
                    'job_id' => $job->job_id,
                    'job_title' => $job->job_title,
                    'job_description' => $job->job_description,
                    'salary' => $job->salary,
                    'work_location' => $job->work_location,
                    'employment_type' => $job->employment_type,
                    'status' => $job->status,
                ])->values();
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

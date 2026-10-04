<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'application_id' => $this->application_id,
            'job' => [
                'job_id' => $this->job?->job_id,
                'job_title' => $this->job?->job_title,
            ],
            'applicant' => [
                'user_id' => $this->applicant?->user_id,
                'full_name' => $this->applicant?->full_name,
                'email' => $this->applicant?->email,
            ],
            'cover_letter' => $this->cover_letter,
            'status' => $this->status,
            'resume_url' => route('job-applications.resume', [
                'job' => $this->job_id,
                'application' => $this->application_id,
            ]),
            'submitted_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

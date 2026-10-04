<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'job_id' => $this->job_id,
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
            'has_authorization_document' => $this->when(
                $request->user()?->role === 'admin',
                $this->document !== null
            ),
            'applications_count' => $this->whenCounted('applications'),

            'company' => [
                'company_id' => $this->company->company_id ?? null,
                'company_name' => $this->company->company_name ?? null,
            ],

            'job_function' => [
                'function_id' => $this->jobFunction->function_id ?? null,
                'function_name' => $this->jobFunction->function_name ?? null,
            ],

            'job_title' => $this->job_title,
            'job_description' => $this->job_description,
            'salary' => $this->salary,
            'work_location' => $this->work_location,
            'employment_type' => $this->employment_type,
            'status' => $this->status,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

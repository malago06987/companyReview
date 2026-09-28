<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'company_id' => $this->company_id,
            'company_name' => $this->company_name,
            'logo_image' => $this->logo_image,
            'description' => $this->description,
            'industry' => $this->industry->industry_name ?? null,
            'address' => $this->address,
            'benefits' => $this->benefits,
            'culture' => $this->culture,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

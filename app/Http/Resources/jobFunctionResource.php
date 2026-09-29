<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobFunctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'function_id' => $this->function_id,
            'function_name' => $this->function_name,
        ];
    }
}

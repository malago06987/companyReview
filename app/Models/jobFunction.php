<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class jobFunction extends Model
{
    protected $table = 'job_functions';
    protected $primaryKey = 'function_id';

    protected $fillable = [
        'function_name',
    ];

    public function jobs() {
    return $this->hasMany(job::class, 'function_id', 'function_id');
}
}

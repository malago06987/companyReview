<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    protected $primaryKey = 'application_id';

    protected $fillable = [
        'job_id',
        'user_id',
        'resume_path',
        'cover_letter',
        'status',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(job::class, 'job_id', 'job_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(user::class, 'user_id', 'user_id');
    }
}

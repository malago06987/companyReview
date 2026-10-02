<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class job extends Model
{
    //
    use HasFactory, SoftDeletes;

    protected $table = 'jobs';
    protected $primaryKey = 'job_id';

    protected $fillable = [
        'company_id',
        'function_id',
        'job_title',
        'job_description',
        'salary',
        'work_location',
        'employment_type',
        'status',
    ];
public function company() {
    return $this->belongsTo(company::class, 'company_id', 'company_id');
}
public function jobFunction() {
    return $this->belongsTo(jobFunction::class, 'function_id', 'function_id');
}

}

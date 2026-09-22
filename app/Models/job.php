<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class job extends Model
{
    //
    use SoftDeletes; // 2. เรียกใช้งาน Trait SoftDeletes ตรงนี้

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

<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class company extends Model
{
    //
    use SoftDeletes;
    protected $table = 'companies';
    protected $primaryKey = 'company_id';
    protected $fillable = [
        'company_name',
        'logo_image',
        'description',
        'industry_id',
        'address',
        'benefits',
        'culture',
    ];

    public function industry() {
    return $this->belongsTo(industry::class, 'industry_id', 'industry_id');
}
public function reviews() {
    return $this->hasMany(review::class, 'company_id', 'company_id');
}
public function jobs() {
    return $this->hasMany(job::class, 'company_id', 'company_id');
}
}

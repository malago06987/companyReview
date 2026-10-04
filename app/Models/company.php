<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class company extends Model
{
    //
    use HasFactory, SoftDeletes;
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
        'approval_status',
        'rejection_reason',
        'user_id',
        'document',
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
public function user() {
    return $this->belongsTo(user::class, 'user_id', 'user_id');
}
}

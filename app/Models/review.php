<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class review extends Model
{
    //
    use HasFactory, SoftDeletes;

    protected $table = 'reviews';
    protected $primaryKey = 'review_id';

    protected $fillable = [
        'company_id',
        'user_id',
        'rating_life',
        'rating_work',
        'rating_money',
        'rating_society',
        'review_text',
        'status',
    ];

    public function company() {
    return $this->belongsTo(company::class, 'company_id', 'company_id');
}
public function user() {
    return $this->belongsTo(user::class, 'user_id', 'user_id');
}
}

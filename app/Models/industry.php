<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class industry extends Model
{
    use HasFactory;

    protected $table = 'industries';
    protected $primaryKey = 'industry_id';

    protected $fillable = [
        'industry_name',
    ];

public function companies() {
    return $this->hasMany(company::class, 'industry_id', 'industry_id');
}

}

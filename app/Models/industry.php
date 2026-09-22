<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class industry extends Model
{
    protected $table = 'industries';
    protected $primaryKey = 'industry_id';

    protected $fillable = [
        'industry_name',
    ];

public function companies() {
    return $this->hasMany(company::class, 'industry_id', 'industry_id');
}

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criteria extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sub_criteria_ids' => 'array',
    ];

    public function subCriteria()
    {
        return $this->belongsTo(SubCriteria::class, 'sub_criteria_id');
    }
}

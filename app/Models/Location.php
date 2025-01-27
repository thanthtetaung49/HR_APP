<?php

namespace App\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function teams () {
        return $this->hasMany(Team::class, 'location_id');
    }
}

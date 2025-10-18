<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportPermission extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function location() {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function team() {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function desingation() {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}

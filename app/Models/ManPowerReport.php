<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManPowerReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function teams () {
        return $this->belongsTo(Team::class, 'team_id');
    }

    // public function location () {
    //     return $this->belongsTo(Location::class, 'location_id');
    // }

    public function designation() {
        return $this->belongsTo(Designation::class, 'position_id');
    }

    public function recruitmentJobs()
    {
        return $this->hasMany(\Modules\Recruit\Entities\RecruitJob::class, 'man_power_report_id');
    }
}

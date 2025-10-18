<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManPowerReportHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function manPowerReport()
    {
        return $this->belongsTo(ManPowerReport::class, 'man_power_report_id');
    }
}

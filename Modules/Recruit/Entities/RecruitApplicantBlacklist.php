<?php

namespace Modules\Recruit\Entities;

use App\Models\BaseModel;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitApplicantBlacklist extends BaseModel
{
    use HasCompany;

    protected $guarded = [];
}

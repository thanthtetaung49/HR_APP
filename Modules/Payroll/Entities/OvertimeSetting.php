<?php

namespace Modules\Payroll\Entities;

use App\Models\BaseModel;
use App\Models\Company;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeSetting extends BaseModel
{
    use HasCompany;

    protected $table = 'overtime_settings';

    protected $guarded = ['id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    public static function current(): self
    {
        return self::firstOrCreate(
            [
                'company_id' => company()->id,
            ],
            [
                'manager_permission' =>
                'cannot-approve',
            ]
        );
    }
}

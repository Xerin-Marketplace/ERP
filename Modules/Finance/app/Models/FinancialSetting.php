<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialSetting extends Model
{
    protected $table = 'financial_settings';

    protected $fillable = ['company_id','base_currency','decimal_places','require_journal_approval','journal_approval_threshold'];

    protected $casts = [
        'journal_approval_threshold' => 'decimal:2',
        'require_journal_approval' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
}

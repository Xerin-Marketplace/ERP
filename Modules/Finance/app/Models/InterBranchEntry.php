<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class InterBranchEntry extends Model
{
    protected $table = 'inter_branch_entries';

    protected $guarded = ['id', 'reference'];

    protected $casts = ['entry_date' => 'date', 'settled_at' => 'datetime', 'amount' => 'decimal:2'];

    public function spentBranch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'spent_branch_id'); }
    public function owedBranch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'owed_branch_id'); }
    public function journal() { return $this->belongsTo(Journal::class); }
}
